<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentActivityFeed;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The "ดูทั้งหมด" destination behind the dashboard's three preview lists
 * (approved / pending / rejected), each capped at 5 there — same merged
 * feed as App\Services\StudentActivityFeed, just paginated instead of
 * take(5)'d. Scoped to one student's own history, so unlike the admin
 * audit log this never grows unbounded across the whole system.
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

        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = new LengthAwarePaginator(
            $items->forPage($page, self::PER_PAGE)->values(),
            $items->count(),
            self::PER_PAGE,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('student.activity-history.index', compact('items', 'status'));
    }
}
