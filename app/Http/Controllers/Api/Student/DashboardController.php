<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardFeedItemResource;
use App\Http\Resources\DashboardSummaryResource;
use App\Services\ActivityEvaluationService;
use App\Services\StudentActivityFeed;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const PREVIEW_LIMIT = 5;

    public function show(Request $request, ActivityEvaluationService $evaluator, StudentActivityFeed $feed)
    {
        $user = $request->user();

        abort_if($user->isAdmin(), 403, 'Admins should use the web admin panel');

        $summary = $evaluator->summarize($user);

        $items = $feed->approvedAndPending($user);
        $approved = $items->where('is_approved', true);
        $pending = $items->where('is_approved', false);
        $rejected = $feed->rejected($user);

        return response()->json([
            'summary' => new DashboardSummaryResource($summary),
            'approved' => DashboardFeedItemResource::collection($approved->take(self::PREVIEW_LIMIT)->values()),
            'pending' => DashboardFeedItemResource::collection($pending->take(self::PREVIEW_LIMIT)->values()),
            'rejected' => DashboardFeedItemResource::collection($rejected->take(self::PREVIEW_LIMIT)->values()),
            // "ดูทั้งหมด" only makes sense once the preview is actually hiding
            // something — see the mobile/web activity-history screens.
            'has_more_approved' => $approved->count() > self::PREVIEW_LIMIT,
            'has_more_pending' => $pending->count() > self::PREVIEW_LIMIT,
            'has_more_rejected' => $rejected->count() > self::PREVIEW_LIMIT,
        ]);
    }
}
