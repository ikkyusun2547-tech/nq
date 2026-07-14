<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardFeedItemResource;
use App\Services\StudentActivityFeed;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Mobile counterpart of Student\ActivityHistoryController (web) — the
 * "ดูทั้งหมด" destination behind the dashboard's three preview lists.
 */
class ActivityHistoryController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request, StudentActivityFeed $feed)
    {
        $status = $request->query('status');
        $status = in_array($status, ['approved', 'pending', 'rejected'], true) ? $status : 'approved';

        $user = $request->user();
        $items = match ($status) {
            'approved' => $feed->approvedAndPending($user)->where('is_approved', true)->values(),
            'pending' => $feed->approvedAndPending($user)->where('is_approved', false)->values(),
            'rejected' => $feed->rejected($user),
        };

        $page = (int) $request->query('page', 1);
        $paginator = new LengthAwarePaginator(
            $items->forPage($page, self::PER_PAGE)->values(),
            $items->count(),
            self::PER_PAGE,
            $page,
        );

        return response()->json([
            'items' => DashboardFeedItemResource::collection($paginator->items()),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
