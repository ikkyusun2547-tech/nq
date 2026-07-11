<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelfReportCheckInRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Activity;
use App\Models\User;
use App\Notifications\AttendanceFlagged;
use App\Services\AttendanceAutomationService;
use App\Services\SafeNotifier;
use Illuminate\Validation\ValidationException;

class SelfCheckInController extends Controller
{
    public function store(Activity $activity, SelfReportCheckInRequest $request, AttendanceAutomationService $service)
    {
        abort_unless($activity->usesSelfReportCheckIn(), 404);

        try {
            $attendance = $service->selfReportCheckIn($request->user(), $activity, $request->file('photo'));
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'errors' => $e->errors()], 422);
        }

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        SafeNotifier::send($admins, new AttendanceFlagged($attendance->load(['user', 'activity'])));

        return response()->json([
            'message' => __('ส่งหลักฐานการเข้าร่วมสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'),
            'attendance' => new AttendanceResource($attendance),
        ]);
    }
}
