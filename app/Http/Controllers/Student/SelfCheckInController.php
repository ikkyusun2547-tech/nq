<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelfReportCheckInRequest;
use App\Models\Activity;
use App\Models\User;
use App\Notifications\AttendanceFlagged;
use App\Services\AttendanceAutomationService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SelfCheckInController extends Controller
{
    public function show(Activity $activity)
    {
        abort_unless($activity->usesSelfReportCheckIn(), 404);

        return view('student.self-checkin', compact('activity'));
    }

    public function store(Activity $activity, SelfReportCheckInRequest $request, AttendanceAutomationService $service)
    {
        abort_unless($activity->usesSelfReportCheckIn(), 404);

        try {
            $attendance = $service->selfReportCheckIn($request->user(), $activity, $request->file('photo'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        Notification::send($admins, new AttendanceFlagged($attendance->load(['user', 'activity'])));

        return redirect()
            ->route('activities.index')
            ->with('status', __('ส่งหลักฐานการเข้าร่วมสำเร็จ รอเจ้าหน้าที่ตรวจสอบ'));
    }
}
