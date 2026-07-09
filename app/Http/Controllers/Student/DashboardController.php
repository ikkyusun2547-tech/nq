<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ActivityEvaluationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request, ActivityEvaluationService $evaluator)
    {
        $user = $request->user();
        $summary = $evaluator->summarize($user);

        $checkins = $user->attendances()
            ->with('activity')
            ->whereIn('status', ['auto_approved', 'flagged'])
            ->get()
            ->map(fn ($att) => (object) [
                'title' => $att->activity->title,
                'date' => $att->checkin_time,
                'hours' => $att->activity->credit_hours,
                'type' => 'checkin',
                'is_approved' => $att->status === 'auto_approved',
            ]);

        $externalRequests = $user->externalActivityRequests()
            ->whereIn('status', ['approved', 'pending'])
            ->get()
            ->map(fn ($ext) => (object) [
                'title' => $ext->title,
                'date' => $ext->activity_date,
                'hours' => $ext->hours_credited,
                'type' => 'external',
                'is_approved' => $ext->status === 'approved',
            ]);

        $items = $checkins->concat($externalRequests)->sortByDesc('date')->values();

        $approvedActivities = $items->where('is_approved', true)->take(5);
        $pendingActivities = $items->where('is_approved', false)->take(5);

        return view('student.dashboard', compact('summary', 'approvedActivities', 'pendingActivities'));
    }
}
