<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LateCheckInRequest;
use App\Notifications\LateCheckInRequestReviewed;
use App\Services\SafeNotifier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LateCheckInApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $requests = LateCheckInRequest::with(['user.faculty', 'user.major', 'activity'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($q) use ($search) {
                    $q->whereHas('activity', fn ($activityQuery) => $activityQuery->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name_thai', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('student_id', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.late-checkins.index', compact('requests', 'status'));
    }

    public function approve(Request $request, LateCheckInRequest $lateCheckInRequest)
    {
        abort_if($lateCheckInRequest->status !== 'pending', 422, __('คำร้องนี้ถูกดำเนินการไปแล้ว'));

        $validated = $request->validate([
            'hours_credited' => ['nullable', 'integer', 'min:0', 'max:200'],
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        // Only store an override when it actually differs from the
        // activity's normal credit_hours — null means "credited as usual"
        // and keeps the common case (no adjustment) free of redundant data.
        // Cast before comparing: validated input is a numeric string from
        // the request, while credit_hours comes back from Eloquent as int.
        $hoursCredited = isset($validated['hours_credited']) ? (int) $validated['hours_credited'] : null;
        if ($hoursCredited !== null && $hoursCredited === $lateCheckInRequest->activity->credit_hours) {
            $hoursCredited = null;
        }

        try {
            DB::transaction(function () use ($lateCheckInRequest, $validated, $hoursCredited, $request) {
                Attendance::create([
                    'user_id' => $lateCheckInRequest->user_id,
                    'activity_id' => $lateCheckInRequest->activity_id,
                    'checkin_method' => 'late_request',
                    'checkin_time' => now(),
                    'photo_path' => $lateCheckInRequest->proof_image_path,
                    'credited_hours' => $hoursCredited,
                    'status' => 'auto_approved',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);

                $lateCheckInRequest->update([
                    'status' => 'approved',
                    'hours_approved' => $hoursCredited,
                    'admin_comment' => $validated['admin_comment'] ?? null,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error', __('นักศึกษาคนนี้มีการเช็คชื่อกิจกรรมนี้อยู่แล้ว ไม่สามารถอนุมัติซ้ำได้'));
            }

            throw $e;
        }

        SafeNotifier::send($lateCheckInRequest->user, new LateCheckInRequestReviewed($lateCheckInRequest));

        return back()->with('status', __('อนุมัติคำร้องสำเร็จ'));
    }

    public function reject(Request $request, LateCheckInRequest $lateCheckInRequest)
    {
        abort_if($lateCheckInRequest->status !== 'pending', 422, __('คำร้องนี้ถูกดำเนินการไปแล้ว'));

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:500'],
            'admin_comment' => ['nullable', 'string', 'max:500'],
        ]);

        $lateCheckInRequest->update([
            'status' => 'rejected',
            'reject_reason' => $validated['reject_reason'],
            'admin_comment' => $validated['admin_comment'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        SafeNotifier::send($lateCheckInRequest->user, new LateCheckInRequestReviewed($lateCheckInRequest));

        return back()->with('status', __('ปฏิเสธคำร้องสำเร็จ'));
    }
}
