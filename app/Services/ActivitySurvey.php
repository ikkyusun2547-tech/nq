<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivitySurveyAnswer;
use App\Models\ActivitySurveyResponse;
use App\Models\Attendance;
use App\Models\SurveyQuestion;
use App\Models\User;
use App\Notifications\ActivitySurveyRequested;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Post-activity satisfaction survey (optional — never affects hours).
 * Anyone who checked in (and wasn't rejected) can answer once the activity
 * has ended. Results are anonymous: see the migration for how "who answered"
 * is kept apart from "what was answered".
 */
class ActivitySurvey
{
    /** Standard 5-point Likert interpretation bands, highest first. */
    private const LEVELS = [
        [4.51, 'มากที่สุด'],
        [3.51, 'มาก'],
        [2.51, 'ปานกลาง'],
        [1.51, 'น้อย'],
        [0.0, 'น้อยที่สุด'],
    ];

    public static function interpret(float $mean): string
    {
        foreach (self::LEVELS as [$min, $label]) {
            if (round($mean, 2) >= $min) {
                return $label;
            }
        }

        return self::LEVELS[array_key_last(self::LEVELS)][1];
    }

    public function hasEnded(Activity $activity): bool
    {
        return $activity->status === 'closed'
            || ($activity->status !== 'cancelled' && $activity->end_at->isPast());
    }

    public function attended(User $user, Activity $activity): bool
    {
        return Attendance::where('user_id', $user->id)
            ->where('activity_id', $activity->id)
            ->where('status', '!=', 'rejected')
            ->exists();
    }

    public function hasSubmitted(User $user, Activity $activity): bool
    {
        return DB::table('activity_survey_submissions')
            ->where('user_id', $user->id)
            ->where('activity_id', $activity->id)
            ->exists();
    }

    public function canAnswer(User $user, Activity $activity): bool
    {
        return $this->hasEnded($activity)
            && $this->attended($user, $activity)
            && ! $this->hasSubmitted($user, $activity);
    }

    /**
     * Ended activities this student attended but hasn't evaluated yet.
     *
     * @return Collection<int, Activity>
     */
    public function pendingFor(User $user): Collection
    {
        return Activity::query()
            ->whereIn('id', Attendance::where('user_id', $user->id)->where('status', '!=', 'rejected')->select('activity_id'))
            ->whereNotIn('id', DB::table('activity_survey_submissions')->where('user_id', $user->id)->select('activity_id'))
            ->where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->where('status', 'closed')->orWhere('end_at', '<', now()))
            ->orderByDesc('end_at')
            ->get();
    }

    /**
     * @param  array<int|string, int|string>  $scores  question id => 1..5, one per active question
     *
     * @throws ValidationException
     */
    public function submit(User $user, Activity $activity, array $scores, ?string $comment): void
    {
        if (! $this->canAnswer($user, $activity)) {
            throw ValidationException::withMessages([
                'scores' => __('คุณไม่สามารถประเมินกิจกรรมนี้ได้ หรือประเมินไปแล้ว'),
            ]);
        }

        $questionIds = SurveyQuestion::active()->pluck('id');

        try {
            DB::transaction(function () use ($user, $activity, $scores, $comment, $questionIds) {
                DB::table('activity_survey_submissions')->insert([
                    'activity_id' => $activity->id,
                    'user_id' => $user->id,
                ]);

                $response = ActivitySurveyResponse::create([
                    'activity_id' => $activity->id,
                    'comment' => filled($comment) ? trim($comment) : null,
                ]);

                foreach ($questionIds as $questionId) {
                    ActivitySurveyAnswer::create([
                        'response_id' => $response->id,
                        'question_id' => $questionId,
                        'score' => (int) $scores[$questionId],
                    ]);
                }
            });
        } catch (QueryException $e) {
            // Double submit racing past canAnswer() — the unique key wins.
            if ($e->getCode() === '23000') {
                throw ValidationException::withMessages(['scores' => __('คุณประเมินกิจกรรมนี้ไปแล้ว')]);
            }

            throw $e;
        }
    }

    /** Invite everyone who attended to evaluate — called when an activity closes. */
    public function requestFromAttendees(Activity $activity): void
    {
        $students = User::whereIn('id', $activity->attendances()->where('status', '!=', 'rejected')->select('user_id'))
            ->whereNotIn('id', DB::table('activity_survey_submissions')->where('activity_id', $activity->id)->select('user_id'))
            ->get();

        if ($students->isNotEmpty() && SurveyQuestion::active()->exists()) {
            SafeNotifier::send($students, new ActivitySurveyRequested($activity));
        }
    }

    /**
     * Per-question mean / S.D. (sample) with Likert interpretation.
     *
     * @return array{respondents: int, attendees: int, questions: list<array{text: string, n: int, mean: float, sd: float, level: string}>, overall: ?array{mean: float, sd: float, level: string}, comments: Collection<int, ActivitySurveyResponse>}
     */
    public function summary(Activity $activity): array
    {
        $rows = DB::table('activity_survey_answers')
            ->join('activity_survey_responses', 'activity_survey_responses.id', '=', 'activity_survey_answers.response_id')
            ->join('survey_questions', 'survey_questions.id', '=', 'activity_survey_answers.question_id')
            ->where('activity_survey_responses.activity_id', $activity->id)
            ->orderBy('survey_questions.sort_order')
            ->orderBy('survey_questions.id')
            ->get(['survey_questions.id', 'survey_questions.text', 'activity_survey_answers.score']);

        $questions = $rows->groupBy('id')
            ->map(fn (Collection $answers) => [
                'text' => $answers->first()->text,
                ...self::stats($answers->pluck('score')),
            ])
            ->values()
            ->all();

        return [
            'respondents' => ActivitySurveyResponse::where('activity_id', $activity->id)->count(),
            'attendees' => $activity->attendances()->where('status', '!=', 'rejected')->count(),
            'questions' => $questions,
            'overall' => $rows->isEmpty() ? null : self::stats($rows->pluck('score')),
            'comments' => ActivitySurveyResponse::where('activity_id', $activity->id)
                ->whereNotNull('comment')
                ->latest('id')
                ->get(['comment', 'created_at']),
        ];
    }

    /**
     * @param  Collection<int, int|string>  $scores
     * @return array{n: int, mean: float, sd: float, level: string}
     */
    public static function stats(Collection $scores): array
    {
        $n = $scores->count();
        $mean = $n ? $scores->avg() : 0.0;
        $sd = $n > 1
            ? sqrt($scores->sum(fn ($s) => ($s - $mean) ** 2) / ($n - 1))
            : 0.0;

        return [
            'n' => $n,
            'mean' => round($mean, 2),
            'sd' => round($sd, 2),
            'level' => self::interpret($mean),
        ];
    }
}
