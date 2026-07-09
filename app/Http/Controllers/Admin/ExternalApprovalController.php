<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalActivityRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExternalApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $requests = ExternalActivityRequest::with(['user.faculty', 'user.major'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name_thai', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('student_id', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('activity_category'), fn ($query) => $query->where('activity_category', $request->input('activity_category')))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.external-activities.index', compact('requests', 'status'));
    }

    public function approve(Request $request, ExternalActivityRequest $externalActivityRequest)
    {
        abort_if($externalActivityRequest->status !== 'pending', 422, __('คำร้องนี้ถูกดำเนินการไปแล้ว'));

        $validated = $request->validate([
            'hours_approved' => ['nullable', 'integer', 'min:0', 'max:200'],
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        // Only store an override when it actually differs from what the
        // student asked for — null means "credited as requested" and keeps
        // the common case (no adjustment) free of redundant data.
        $hoursApproved = $validated['hours_approved'] ?? null;
        if ($hoursApproved !== null && $hoursApproved === $externalActivityRequest->hours_requested) {
            $hoursApproved = null;
        }

        $externalActivityRequest->update([
            'status' => 'approved',
            'hours_approved' => $hoursApproved,
            'admin_comment' => $validated['admin_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', __('อนุมัติคำร้องสำเร็จ'));
    }

    public function reject(Request $request, ExternalActivityRequest $externalActivityRequest)
    {
        abort_if($externalActivityRequest->status !== 'pending', 422, __('คำร้องนี้ถูกดำเนินการไปแล้ว'));

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        $externalActivityRequest->update([
            'status' => 'rejected',
            'reject_reason' => $validated['reject_reason'],
            'admin_comment' => $validated['admin_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', __('ปฏิเสธคำร้องสำเร็จ'));
    }
}
