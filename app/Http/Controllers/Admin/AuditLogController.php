<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CreditTransferRequest;
use App\Models\ExternalActivityRequest;
use App\Models\LateCheckInRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Every approve/reject action already stamps reviewed_by/reviewed_at on its
 * own record (attendance, external activity, credit transfer, late
 * check-in), but until now the only way to see "what has this admin done"
 * was opening each record type's list separately and reading the reviewer
 * column row by row. This merges all four into one chronological feed.
 */
class AuditLogController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request)
    {
        $attendances = Attendance::whereNotNull('reviewed_by')
            ->with(['user', 'activity', 'reviewer'])
            ->get()
            ->map(fn ($att) => (object) [
                'reviewer' => $att->reviewer,
                'action' => $att->status === 'rejected' ? 'rejected' : 'approved',
                'type_label' => __('เช็คชื่อ'),
                'student' => $att->user,
                'title' => $att->activity->title,
                'reviewed_at' => $att->reviewed_at,
            ]);

        $externalRequests = ExternalActivityRequest::whereNotNull('reviewed_by')
            ->with(['user', 'reviewer'])
            ->get()
            ->map(fn ($req) => (object) [
                'reviewer' => $req->reviewer,
                'action' => $req->status,
                'type_label' => __('กิจกรรมภายนอก'),
                'student' => $req->user,
                'title' => $req->title,
                'reviewed_at' => $req->reviewed_at,
            ]);

        $creditTransfers = CreditTransferRequest::whereNotNull('reviewed_by')
            ->with(['user', 'reviewer'])
            ->get()
            ->map(fn ($req) => (object) [
                'reviewer' => $req->reviewer,
                'action' => $req->status,
                'type_label' => __('เทียบโอนตำแหน่ง'),
                'student' => $req->user,
                'title' => __(\App\Models\CreditTransferRequest::POSITION_LABELS[$req->position] ?? $req->position),
                'reviewed_at' => $req->reviewed_at,
            ]);

        $lateCheckIns = LateCheckInRequest::whereNotNull('reviewed_by')
            ->with(['user', 'activity', 'reviewer'])
            ->get()
            ->map(fn ($req) => (object) [
                'reviewer' => $req->reviewer,
                'action' => $req->status,
                'type_label' => __('เช็คชื่อย้อนหลัง'),
                'student' => $req->user,
                'title' => $req->activity->title,
                'reviewed_at' => $req->reviewed_at,
            ]);

        $entries = $attendances
            ->concat($externalRequests)
            ->concat($creditTransfers)
            ->concat($lateCheckIns)
            ->when($request->filled('reviewer_id'), fn ($c) => $c->where('reviewer.id', (int) $request->input('reviewer_id')))
            ->sortByDesc('reviewed_at')
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $entries = new LengthAwarePaginator(
            $entries->forPage($page, self::PER_PAGE)->values(),
            $entries->count(),
            self::PER_PAGE,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $reviewers = collect($attendances)
            ->concat($externalRequests)
            ->concat($creditTransfers)
            ->concat($lateCheckIns)
            ->pluck('reviewer')
            ->filter()
            ->unique('id')
            ->sortBy(fn ($u) => $u->name_thai ?? $u->name)
            ->values();

        return view('admin.audit-log.index', compact('entries', 'reviewers'));
    }
}
