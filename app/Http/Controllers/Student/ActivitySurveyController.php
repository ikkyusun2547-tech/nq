<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\SurveyQuestion;
use App\Services\ActivitySurvey;
use Illuminate\Http\Request;

class ActivitySurveyController extends Controller
{
    public function __construct(private ActivitySurvey $survey) {}

    public function show(Request $request, Activity $activity)
    {
        $user = $request->user();

        abort_unless($this->survey->attended($user, $activity), 403);

        return view('student.activities.survey', [
            'activity' => $activity,
            'questions' => SurveyQuestion::active()->get(),
            'submitted' => $this->survey->hasSubmitted($user, $activity),
            'ended' => $this->survey->hasEnded($activity),
        ]);
    }

    public function store(Request $request, Activity $activity)
    {
        $questionIds = SurveyQuestion::active()->pluck('id');

        $rules = ['comment' => ['nullable', 'string', 'max:2000']];
        foreach ($questionIds as $id) {
            $rules["scores.$id"] = ['required', 'integer', 'between:1,5'];
        }

        $validated = $request->validate($rules, [
            'scores.*.required' => __('กรุณาให้คะแนนให้ครบทุกข้อ'),
        ]);

        $this->survey->submit($request->user(), $activity, $validated['scores'] ?? [], $validated['comment'] ?? null);

        return redirect()
            ->route('activities.show', $activity)
            ->with('status', __('ขอบคุณที่ร่วมประเมินกิจกรรม'));
    }
}
