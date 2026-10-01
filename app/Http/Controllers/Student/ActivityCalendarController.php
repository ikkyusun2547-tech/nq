<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ActivityCalendar;
use Illuminate\Http\Request;

class ActivityCalendarController extends Controller
{
    /**
     * Month view of the same activities the list page shows a student:
     * only ones they're eligible for, never cancelled ones.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $month = ActivityCalendar::resolveMonth($request->query('month'));
        [$from, $to] = ActivityCalendar::gridRange($month);

        $activities = Activity::overlapping($from, $to)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->filter(fn (Activity $activity) => $activity->isEligibleFor($user))
            ->values();

        $checkedInActivityIds = $user->attendances()
            ->whereIn('activity_id', $activities->pluck('id'))
            ->pluck('activity_id')
            ->all();

        return view('student.activities.calendar', [
            'month' => $month,
            'weeks' => ActivityCalendar::weeks($month, $activities),
            'checkedInActivityIds' => $checkedInActivityIds,
        ]);
    }
}
