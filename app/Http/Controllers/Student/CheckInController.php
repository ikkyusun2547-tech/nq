<?php

namespace App\Http\Controllers\Student;

use App\Exceptions\QrTokenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Services\AttendanceAutomationService;
use Illuminate\Validation\ValidationException;

class CheckInController extends Controller
{
    public function show()
    {
        return view('student.checkin');
    }

    public function store(CheckInRequest $request, AttendanceAutomationService $service)
    {
        try {
            $attendance = $service->checkIn(
                user: $request->user(),
                qrToken: $request->string('qr_token')->toString(),
                lat: (float) $request->input('location_lat'),
                lng: (float) $request->input('location_lng'),
                deviceUuid: $request->string('device_uuid')->toString(),
                photo: $request->file('photo'),
            );
        } catch (QrTokenException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json([
            'status' => $attendance->status,
            'distance_meters' => $attendance->distance_meters,
            'message' => $attendance->status === 'auto_approved'
                ? __('เช็กชื่อสำเร็จ! บันทึกชั่วโมงกิจกรรมเรียบร้อยแล้ว')
                : __('เช็กชื่อสำเร็จ แต่ระบบตรวจพบความผิดปกติ กรุณารอเจ้าหน้าที่ตรวจสอบ'),
        ]);
    }
}
