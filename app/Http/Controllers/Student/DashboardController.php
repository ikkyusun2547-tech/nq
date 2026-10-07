<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\LateCheckInRequest;
use Carbon\CarbonImmutable;
use App\Services\ActivityEvaluationService;
use App\Services\StudentActivityFeed;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const PREVIEW_LIMIT = 3;

    public function show(Request $request, ActivityEvaluationService $evaluator, StudentActivityFeed $feed)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $summary = $evaluator->summarize($user);
        $currentPositionLabel = $evaluator->currentPositionLabel($user);

        $items = $feed->approvedAndPending($user);
        $approved = $items->where('is_approved', true);
        $pending = $items->where('is_approved', false);
        $rejected = $feed->rejected($user);

        // One preview per status × type filter, so filtering to e.g. credit
        // transfers still shows the latest few of those rather than whichever
        // happened to be in the overall top three. "ดูทั้งหมด" only makes
        // sense once the preview is actually hiding something.
        $history = collect(['approved' => $approved, 'pending' => $pending, 'rejected' => $rejected])
            ->map(fn ($list) => collect(StudentActivityFeed::TYPE_FILTERS)->mapWithKeys(function ($type) use ($list) {
                $filtered = $type === 'all' ? $list : $list->where('type', $type);

                return [$type => [
                    'items' => $filtered->take(self::PREVIEW_LIMIT)->values(),
                    'has_more' => $filtered->count() > self::PREVIEW_LIMIT,
                    'count' => $filtered->count(),
                ]];
            }));

        // "Today" panel: what can be checked into right now, this week at a
        // glance, and what's coming up — all limited to activities this
        // student is eligible for.
        $weekStart = CarbonImmutable::today()->startOfWeek(CarbonImmutable::MONDAY);
        $attendedIds = $user->attendances()->pluck('activity_id')->flip();

        $activities = Activity::query()
            ->where('status', '!=', 'cancelled')
            ->where('end_at', '>=', $weekStart)
            ->where('start_at', '<=', now()->addDays(90))
            ->orderBy('start_at')
            ->get()
            ->filter(fn (Activity $activity) => $activity->isEligibleFor($user))
            ->values();

        $nowActivities = $activities
            ->filter(fn (Activity $activity) => ! $attendedIds->has($activity->id) && $this->checkInClosesAt($activity) !== null)
            ->sortBy(fn (Activity $activity) => $this->checkInClosesAt($activity))
            ->values()
            ->map(fn (Activity $activity) => [
                'activity' => $activity,
                'opens_at' => $activity->usesSelfReportCheckIn() ? $activity->checkin_opens_at : $activity->start_at,
                'closes_at' => $this->checkInClosesAt($activity),
            ]);

        $upcomingActivities = $activities
            ->filter(fn (Activity $activity) => $activity->start_at->isFuture() && ! $attendedIds->has($activity->id))
            ->take(3)
            ->values();

        // Hero fallbacks (only needed when nothing is open now or coming up):
        // the most recent activity the student missed, else their latest check-in.
        $missedActivity = null;
        $missedLateStatus = null;
        $latestAttendance = null;
        if ($nowActivities->isEmpty() && $upcomingActivities->isEmpty()) {
            $missedActivity = Activity::query()
                ->where('status', 'closed')
                ->where('end_at', '<=', now())
                ->whereNotIn('id', $attendedIds->keys())
                ->orderByDesc('end_at')
                ->limit(20)
                ->get()
                ->first(fn (Activity $activity) => $activity->isEligibleFor($user));

            if ($missedActivity) {
                $missedLateStatus = LateCheckInRequest::where('user_id', $user->id)
                    ->where('activity_id', $missedActivity->id)
                    ->latest('id')
                    ->value('status');
            } else {
                $latestAttendance = $user->attendances()->with('activity')->latest('checkin_time')->first();
            }
        }
        $week = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $activities) {
            $day = $weekStart->addDays($offset);

            return [
                'date' => $day,
                'has_activity' => $activities->contains(
                    fn (Activity $activity) => $activity->start_at->lte($day->endOfDay()) && $activity->end_at->gte($day)
                ),
            ];
        });

        return view('student.dashboard', compact(
            'summary', 'history', 'currentPositionLabel',
            'nowActivities', 'upcomingActivities', 'week', 'missedActivity', 'missedLateStatus', 'latestAttendance',
        ));
    }

    /**
     * When check-in for this activity closes, if it's open right now —
     * else null. Mirrors Activity::acceptsCheckIn(), but a realtime
     * activity only counts as "now" during its scheduled time.
     */
    private function checkInClosesAt(Activity $activity): ?\DateTimeInterface
    {
        if (! in_array($activity->status, ['open', 'ongoing'], true)) {
            return null;
        }

        [$opens, $closes] = $activity->usesSelfReportCheckIn()
            ? [$activity->checkin_opens_at, $activity->checkin_closes_at]
            : [$activity->start_at, $activity->end_at];

        return $opens && $closes && now()->between($opens, $closes) ? $closes : null;
    }
}
