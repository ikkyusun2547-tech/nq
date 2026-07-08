<?php

namespace App\Services;

use App\Exceptions\QrTokenException;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class AttendanceAutomationService
{
    public function __construct(
        protected DynamicQrTokenGenerator $qrTokens,
    ) {}

    /**
     * Process a student's three-factor check-in attempt (QR + GPS + selfie)
     * and decide auto_approved vs flagged.
     *
     * @throws QrTokenException|ValidationException
     */
    public function checkIn(
        User $user,
        string $qrToken,
        float $lat,
        float $lng,
        string $deviceUuid,
        UploadedFile $photo,
    ): Attendance {
        $activity = $this->qrTokens->resolveActivity($qrToken);

        if (! in_array($activity->status, ['open', 'ongoing'], true)) {
            throw ValidationException::withMessages([
                'qr_token' => __('กิจกรรมนี้ไม่ได้เปิดรับเช็กชื่อในขณะนี้'),
            ]);
        }

        if (! $activity->isEligibleFor($user)) {
            throw ValidationException::withMessages([
                'qr_token' => __('คุณไม่มีสิทธิ์เข้าร่วมกิจกรรมนี้'),
            ]);
        }

        if (Attendance::where('user_id', $user->id)->where('activity_id', $activity->id)->exists()) {
            throw ValidationException::withMessages([
                'qr_token' => 'คุณเช็กชื่อกิจกรรมนี้ไปแล้ว',
            ]);
        }

        $distance = HaversineCalculator::distanceInMeters(
            (float) $activity->location_lat,
            (float) $activity->location_lng,
            $lat,
            $lng,
        );

        $deviceReusedByOthers = Attendance::where('activity_id', $activity->id)
            ->where('device_uuid', $deviceUuid)
            ->where('user_id', '!=', $user->id)
            ->exists();

        $reasons = [];

        if ($distance > $activity->allowed_radius) {
            $reasons[] = 'GPS_OUT_OF_BOUNDS';
        }

        if ($deviceReusedByOthers) {
            $reasons[] = 'DEVICE_SHARING_SUSPECTED';
        }

        return Attendance::create([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'checkin_time' => now(),
            'student_lat' => $lat,
            'student_lng' => $lng,
            'distance_meters' => (int) round($distance),
            'device_uuid' => $deviceUuid,
            'photo_path' => $photo->store('attendance-selfies', 'public'),
            'status' => empty($reasons) ? 'auto_approved' : 'flagged',
            'flag_reason' => empty($reasons) ? null : implode(',', $reasons),
        ]);
    }
}
