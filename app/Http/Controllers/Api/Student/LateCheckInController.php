<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\LateCheckInStoreRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\LateCheckInRequestResource;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\LateCheckInRequest;
use App\Models\User;
use App\Notifications\LateCheckInRequestSubmitted;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;

class LateCheckInController extends Controller
{
    public function show(Activity $activity, Request $request)
    {
        $this->ensureActivityAllowsLateRequest($activity, $request);

        $existingRequest = LateCheckInRequest::where('user_id', $request->user()->id)
            ->where('activity_id', $activity->id)
            ->latest('id')
            ->first();

        return response()->json([
            'activity' => new ActivityResource($activity),
            'existing_request' => new LateCheckInRequestResource($existingRequest),
        ]);
    }

    public function store(Activity $activity, LateCheckInStoreRequest $request)
    {
        $this->ensureActivityAllowsLateRequest($activity, $request);
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

        return response()->json([
            'message' => __('ส่งคำร้องขอเช็คชื่อย้อนหลังสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'),
            'request' => new LateCheckInRequestResource($lateCheckInRequest),
        ]);
    }

    protected function ensureActivityAllowsLateRequest(Activity $activity, Request $request): void
    {
        abort_unless($activity->acceptsLateRequest(), 404);
        abort_unless($activity->isEligibleFor($request->user()), 403);
    }

    protected function ensureNoUnresolvedRequest(Activity $activity, User $user): void
    {
        abort_if(
            Attendance::where('user_id', $user->id)->where('activity_id', $activity->id)->exists(),
            422,
            __('คุณเช็คชื่อกิจกรรมนี้ไปแล้ว')
        );

        abort_if(
            LateCheckInRequest::where('user_id', $user->id)
                ->where('activity_id', $activity->id)
                ->whereIn('status', ['pending', 'approved'])
                ->exists(),
            422,
            __('คุณส่งคำร้องขอเช็คชื่อย้อนหลังสำหรับกิจกรรมนี้ไปแล้ว')
        );
    }
}
