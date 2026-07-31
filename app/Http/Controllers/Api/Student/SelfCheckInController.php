<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelfReportCheckInRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Activity;
use App\Services\AttendanceAutomationService;
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

        // Flagged (pending-review) self-reports used to notify admins here
        // — removed in favor of a passive count badge on admin/activities
        // (see Admin\ActivityController::index()'s flagged_count) so a busy
        // admin isn't pinged for every single submission, just shown
        // there's a queue to work through whenever they check.
        return response()->json([
            'message' => $attendance->status === 'auto_approved'
                ? __('เช็คชื่อสำเร็จ! บันทึกชั่วโมงกิจกรรมเรียบร้อยแล้ว')
                : __('ส่งหลักฐานการเข้าร่วมสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'),
            'attendance' => new AttendanceResource($attendance),
        ]);
    }
}
