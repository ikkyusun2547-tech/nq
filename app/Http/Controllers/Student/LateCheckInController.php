<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\LateCheckInStoreRequest;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\LateCheckInRequest;
use App\Models\User;
use App\Notifications\LateCheckInRequestSubmitted;
use App\Services\SafeNotifier;

class LateCheckInController extends Controller
{
    public function show(Activity $activity)
    {
        $this->ensureActivityAllowsLateRequest($activity);

        $existingRequest = LateCheckInRequest::where('user_id', request()->user()->id)
            ->where('activity_id', $activity->id)
            ->latest('id')
            ->first();

        return view('student.late-checkin', compact('activity', 'existingRequest'));
    }

    public function store(Activity $activity, LateCheckInStoreRequest $request)
    {
        $this->ensureActivityAllowsLateRequest($activity);
        $this->ensureNoUnresolvedRequest($activity, $request->user());

        $validated = $request->validated();

        $lateCheckInRequest = LateCheckInRequest::create([
            'user_id' => $request->user()->id,
            'activity_id' => $activity->id,
            'reason' => $validated['reason'],
            'proof_image_path' => $request->file('proof_image')->store('late-checkin-proofs', 'public'),
            'status' => 'pending',
        ]);

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        SafeNotifier::send($admins, new LateCheckInRequestSubmitted($lateCheckInRequest->load(['user', 'activity'])));

        return redirect()
            ->route('activities.index', ['status_group' => 'ended'])
            ->with('status', __('ส่งคำร้องขอเช็กชื่อย้อนหลังสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'));
    }

    /**
     * Baseline gate shared by both viewing and submitting: the activity must
     * actually be closed, and the student must be within its audience.
     */
    protected function ensureActivityAllowsLateRequest(Activity $activity): void
    {
        abort_unless($activity->acceptsLateRequest(), 404);
        abort_unless($activity->isEligibleFor(request()->user()), 403);
    }

    /**
     * Submission-only guard: doesn't already have a real check-in, and
     * doesn't already have an unresolved (or already-approved) request for
     * it — a prior rejection is the only case that allows resubmission.
     * Kept separate from show() so a student can still view the status of
     * an existing request instead of hitting a dead-end error page.
     */
    protected function ensureNoUnresolvedRequest(Activity $activity, User $user): void
    {
        abort_if(
            Attendance::where('user_id', $user->id)->where('activity_id', $activity->id)->exists(),
            422,
            __('คุณเช็กชื่อกิจกรรมนี้ไปแล้ว')
        );

        abort_if(
            LateCheckInRequest::where('user_id', $user->id)
                ->where('activity_id', $activity->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists(),
            422,
            __('คุณส่งคำร้องขอเช็กชื่อย้อนหลังสำหรับกิจกรรมนี้ไปแล้ว')
        );
    }
}
