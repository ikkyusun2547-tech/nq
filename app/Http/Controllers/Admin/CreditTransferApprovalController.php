<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCreditTransferGrantRequest;
use App\Models\CreditTransferPosition;
use App\Models\CreditTransferRequest;
use App\Models\User;
use App\Notifications\CreditTransferRequestReviewed;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreditTransferApprovalController extends Controller
{
    /** ?sort= value => real column to order by. Whitelisted so the query string can never inject an arbitrary column/expression into orderBy(). */
    private const SORTABLE = [
        'position' => 'position',
        'academic_year' => 'academic_year',
        'activity_category' => 'activity_category',
        'hours_requested' => 'hours_requested',
        'created_at' => 'created_at',
    ];

    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $sortField = $request->input('sort');
        $sortColumn = self::SORTABLE[$sortField] ?? null;
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $requestsQuery = CreditTransferRequest::with(['user.faculty', 'user.major'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name_thai', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('position'), fn ($query) => $query->where('position', $request->input('position')));

        // name lives on the related user, not credit_transfer_requests —
        // needs an explicit join (with `select(...*)` so the join's own
        // id/created_at/updated_at columns don't collide with this table's).
        if ($sortField === 'name') {
            $requestsQuery->join('users', 'users.id', '=', 'credit_transfer_requests.user_id')
                ->select('credit_transfer_requests.*')
                ->orderBy('users.name_thai', $sortDir);
        } elseif ($sortColumn) {
            $requestsQuery->orderBy($sortColumn, $sortDir);
        } else {
            $requestsQuery->latest('created_at');
        }

        $requests = $requestsQuery->paginate(20)->withQueryString();

        // Tab-pill counts — independent of search/position filters so they
        // always reflect the true size of each bucket, not just the current view.
        $tabCounts = CreditTransferRequest::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $tabCounts['all'] = $tabCounts->sum();

        return view('admin.credit-transfers.index', compact('requests', 'status', 'tabCounts'));
    }

    public function approve(Request $request, CreditTransferRequest $creditTransferRequest)
    {
        abort_if($creditTransferRequest->status !== 'pending', 422, __('คำร้องนี้ถูกดำเนินการไปแล้ว'));

        $validated = $request->validate([
            'activity_category' => ['required', Rule::in(['culture', 'academic', 'sports', 'volunteer', 'ethics'])],
            'hours_approved' => ['nullable', 'integer', 'min:0', 'max:200'],
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        // Only store an override when it actually differs from the
        // position's standard hours — null means "credited as usual" and
        // keeps the common case (no adjustment) free of redundant data.
        $hoursApproved = $validated['hours_approved'] ?? null;
        if ($hoursApproved !== null && $hoursApproved === $creditTransferRequest->hours_requested) {
            $hoursApproved = null;
        }

        $creditTransferRequest->update([
            'status' => 'approved',
            'activity_category' => $validated['activity_category'],
            'hours_approved' => $hoursApproved,
            'admin_comment' => $validated['admin_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        SafeNotifier::send($creditTransferRequest->user, new CreditTransferRequestReviewed($creditTransferRequest));

        return back()->with('status', __('อนุมัติคำร้องสำเร็จ'));
    }

    public function reject(Request $request, CreditTransferRequest $creditTransferRequest)
    {
        abort_if($creditTransferRequest->status !== 'pending', 422, __('คำร้องนี้ถูกดำเนินการไปแล้ว'));

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        $creditTransferRequest->update([
            'status' => 'rejected',
            'reject_reason' => $validated['reject_reason'],
            'admin_comment' => $validated['admin_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        SafeNotifier::send($creditTransferRequest->user, new CreditTransferRequestReviewed($creditTransferRequest));

        return back()->with('status', __('ปฏิเสธคำร้องสำเร็จ'));
    }

    /**
     * Super-admin-only shortcut that skips the usual "student submits,
     * admin reviews" flow entirely — lands the row already approved, for
     * cases where the professor/registrar already knows a student qualifies
     * and the student never filed a request themselves. Still bound by the
     * same position→hours table and one-claim-per-academic-year rule as the
     * student-submitted flow (AdminCreditTransferGrantRequest).
     */
    public function grant(AdminCreditTransferGrantRequest $request, User $student)
    {
        abort_unless($student->role === 'student', 404);

        $validated = $request->validated();
        $position = $validated['position'];
        $hoursRequested = CreditTransferPosition::hoursMap()[$position];

        // Same "only store an override when it actually differs" rule as
        // approve() — null means "credited at the position's standard
        // hours" and keeps the common case free of redundant data.
        $hoursApproved = $validated['hours_approved'] ?? null;
        if ($hoursApproved !== null && $hoursApproved === $hoursRequested) {
            $hoursApproved = null;
        }

        $creditTransferRequest = CreditTransferRequest::create([
            'user_id' => $student->id,
            'position' => $position,
            'academic_year' => $validated['academic_year'],
            'hours_requested' => $hoursRequested,
            'hours_approved' => $hoursApproved,
            'activity_category' => $validated['activity_category'],
            'proof_image_path' => $request->file('proof_image')?->store('credit-transfer-proofs', 'public'),
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        SafeNotifier::send($student, new CreditTransferRequestReviewed($creditTransferRequest));

        return redirect()
            ->route('admin.students.show', $student)
            ->with('status', __('เพิ่มชั่วโมงเทียบโอนตำแหน่งให้นักศึกษาสำเร็จ'));
    }

    /**
     * Super-admin-only undo for an approval made in error (student claimed
     * the wrong position, admin approved without catching it, etc.). Flips
     * an approved request back to 'rejected' rather than deleting it or
     * introducing a new status — the hours drop out of the student's total
     * immediately since ActivityEvaluationService only sums 'approved'
     * rows, and the existing reject_reason/notification machinery already
     * does everything else a reversal needs.
     */
    public function revoke(Request $request, CreditTransferRequest $creditTransferRequest)
    {
        abort_if($creditTransferRequest->status !== 'approved', 422, __('คำร้องนี้ไม่ได้อยู่ในสถานะอนุมัติ'));

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        $creditTransferRequest->update([
            'status' => 'rejected',
            'reject_reason' => $validated['reject_reason'],
            'admin_comment' => $validated['admin_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        SafeNotifier::send($creditTransferRequest->user, new CreditTransferRequestReviewed($creditTransferRequest));

        return back()->with('status', __('ยกเลิกการอนุมัติสำเร็จ ชั่วโมงถูกตัดออกจากยอดสะสมแล้ว'));
    }
}
