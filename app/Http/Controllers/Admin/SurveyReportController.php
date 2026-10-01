<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ActivitySurvey;
use App\Services\AcademicYearCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyReportController extends Controller
{
    /**
     * Satisfaction across every activity that has responses, best first,
     * plus a per-category roll-up — for spotting which kinds of activity
     * land well and which need rethinking.
     */
    public function index(Request $request)
    {
        $academicYears = Activity::whereNotNull('academic_year')->distinct()->orderByDesc('academic_year')->pluck('academic_year');
        $academicYear = $request->has('academic_year')
            ? (string) $request->input('academic_year')
            : (string) AcademicYearCalculator::forDate(now());

        $scores = DB::table('activity_survey_answers')
            ->join('activity_survey_responses', 'activity_survey_responses.id', '=', 'activity_survey_answers.response_id')
            ->join('activities', 'activities.id', '=', 'activity_survey_responses.activity_id')
            ->when($academicYear !== '', fn ($q) => $q->where('activities.academic_year', $academicYear))
            ->get(['activities.id as activity_id', 'activities.activity_category', 'activity_survey_answers.score']);

        $activityIds = $scores->pluck('activity_id')->unique();
        $respondents = DB::table('activity_survey_responses')
            ->whereIn('activity_id', $activityIds)
            ->groupBy('activity_id')
            ->pluck(DB::raw('count(*)'), 'activity_id');

        $activities = Activity::whereIn('id', $activityIds)
            ->withCount([
                'attendances as attendees_count' => fn ($q) => $q->where('status', '!=', 'rejected'),
            ])
            ->get()
            ->map(function (Activity $activity) use ($scores, $respondents) {
                $activity->survey = ActivitySurvey::stats($scores->where('activity_id', $activity->id)->pluck('score'));
                $activity->respondents = (int) ($respondents[$activity->id] ?? 0);

                return $activity;
            })
            ->sortByDesc(fn (Activity $a) => $a->survey['mean'])
            ->values();

        $byCategory = $scores->groupBy('activity_category')
            ->map(fn ($rows) => ActivitySurvey::stats($rows->pluck('score')));

        $overall = $scores->isEmpty() ? null : ActivitySurvey::stats($scores->pluck('score'));

        return view('admin.reports.survey', compact('activities', 'byCategory', 'overall', 'academicYears', 'academicYear'));
    }
}
