<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ActivityEvaluationService;
use App\Services\StudentActivityFeed;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const PREVIEW_LIMIT = 5;

    public function show(Request $request, ActivityEvaluationService $evaluator, StudentActivityFeed $feed)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $summary = $evaluator->summarize($user);

        $items = $feed->approvedAndPending($user);
        $approved = $items->where('is_approved', true);
        $pending = $items->where('is_approved', false);
        $rejected = $feed->rejected($user);

        $approvedActivities = $approved->take(self::PREVIEW_LIMIT);
        $pendingActivities = $pending->take(self::PREVIEW_LIMIT);
        $rejectedActivities = $rejected->take(self::PREVIEW_LIMIT);

        // "ดูทั้งหมด" only makes sense once the preview is actually hiding
        // something — otherwise it's a link to a page showing the same 5
        // rows the student is already looking at.
        $hasMoreApproved = $approved->count() > self::PREVIEW_LIMIT;
        $hasMorePending = $pending->count() > self::PREVIEW_LIMIT;
        $hasMoreRejected = $rejected->count() > self::PREVIEW_LIMIT;

        return view('student.dashboard', compact(
            'summary', 'approvedActivities', 'pendingActivities', 'rejectedActivities',
            'hasMoreApproved', 'hasMorePending', 'hasMoreRejected',
        ));
    }
}
