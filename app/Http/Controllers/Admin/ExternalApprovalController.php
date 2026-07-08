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

        $requests = ExternalActivityRequest::with('user')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.external-activities.index', compact('requests', 'status'));
    }

    public function approve(Request $request, ExternalActivityRequest $externalActivityRequest)
    {
        abort_if($externalActivityRequest->status !== 'pending', 422, 'คำร้องนี้ถูกดำเนินการไปแล้ว');

        $externalActivityRequest->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'อนุมัติคำร้องสำเร็จ');
    }

    public function reject(Request $request, ExternalActivityRequest $externalActivityRequest)
    {
        abort_if($externalActivityRequest->status !== 'pending', 422, 'คำร้องนี้ถูกดำเนินการไปแล้ว');

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
        ]);

        $externalActivityRequest->update([
            'status' => 'rejected',
            'reject_reason' => $validated['reject_reason'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'ปฏิเสธคำร้องสำเร็จ');
    }
}
