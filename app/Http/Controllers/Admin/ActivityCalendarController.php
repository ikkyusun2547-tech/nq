<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ActivityCalendar;
use Illuminate\Http\Request;

class ActivityCalendarController extends Controller
{
    /**
     * Every activity in the month regardless of status (drafts and
     * cancelled ones included), so admins can spot scheduling clashes.
     */
    public function index(Request $request)
    {
        $month = ActivityCalendar::resolveMonth($request->query('month'));
        [$from, $to] = ActivityCalendar::gridRange($month);

        $activities = Activity::overlapping($from, $to)->get();

        return view('admin.activities.calendar', [
            'month' => $month,
            'weeks' => ActivityCalendar::weeks($month, $activities),
        ]);
    }
}
