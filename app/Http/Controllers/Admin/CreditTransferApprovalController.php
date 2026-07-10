<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransferRequest;
use App\Notifications\CreditTransferRequestReviewed;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreditTransferApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $requests = CreditTransferRequest::with(['user.faculty', 'user.major'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name_thai', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('position'), fn ($query) => $query->where('position', $request->input('position')))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.credit-transfers.index', compact('requests', 'status'));
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

        $creditTransferRequest->user->notify(new CreditTransferRequestReviewed($creditTransferRequest));

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

        $creditTransferRequest->user->notify(new CreditTransferRequestReviewed($creditTransferRequest));

        return back()->with('status', __('ปฏิเสธคำร้องสำเร็จ'));
    }
}
