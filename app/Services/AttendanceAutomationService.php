<?php

namespace App\Services;

use App\Exceptions\QrTokenException;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttendanceAutomationService
{
    public function __construct(
        protected DynamicQrTokenGenerator $qrTokens,
    ) {}

    /**
     * Process a student's check-in attempt (QR + selfie, plus GPS when the
     * activity requires it) and decide auto_approved vs flagged.
     *
     * @throws QrTokenException|ValidationException
     */
    public function checkIn(
        User $user,
        string $qrToken,
        ?float $lat,
        ?float $lng,
        string $deviceUuid,
        UploadedFile $photo,
    ): Attendance {
        [$activity, $isPrintedQr] = $this->qrTokens->resolveActivity($qrToken);

        if (! $activity->acceptsCheckIn()) {
            throw ValidationException::withMessages([
                'qr_token' => __('กิจกรรมนี้ไม่ได้เปิดรับเช็คชื่อในขณะนี้'),
            ]);
        }

        if (! $activity->isEligibleFor($user)) {
            throw ValidationException::withMessages([
                'qr_token' => __('คุณไม่มีสิทธิ์เข้าร่วมกิจกรรมนี้'),
            ]);
        }

        if (Attendance::where('user_id', $user->id)->where('activity_id', $activity->id)->exists()) {
            throw ValidationException::withMessages([
                'qr_token' => 'คุณเช็คชื่อกิจกรรมนี้ไปแล้ว',
            ]);
        }

        if ($activity->requiresGpsCheck() && ($lat === null || $lng === null)) {
            throw ValidationException::withMessages([
                'location_lat' => __('กิจกรรมนี้ต้องระบุตำแหน่ง GPS เพื่อเช็คชื่อ'),
            ]);
        }

        $distance = $activity->requiresGpsCheck()
            ? HaversineCalculator::distanceInMeters(
                (float) $activity->location_lat,
                (float) $activity->location_lng,
                $lat,
                $lng,
            )
            : null;

        $deviceReusedByOthers = Attendance::where('activity_id', $activity->id)
            ->where('device_uuid', $deviceUuid)
            ->where('user_id', '!=', $user->id)
            ->exists();

        $reasons = [];

        if ($distance !== null && $distance > $activity->allowed_radius) {
            $reasons[] = 'GPS_OUT_OF_BOUNDS';
        }

        if ($deviceReusedByOthers) {
            $reasons[] = 'DEVICE_SHARING_SUSPECTED';
        }

        // The printable QR can't rotate, so it can't rule out a screenshot
        // being reused by someone who wasn't actually there — GPS and selfie
        // still get recorded and checked as normal, but the check-in itself
        // can never auto-approve on this weaker guarantee alone.
        if ($isPrintedQr) {
            $reasons[] = 'PRINTED_QR_USED';
        }

        $photoPath = $photo->store('attendance-selfies', 'public');

        try {
            return Attendance::create([
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'checkin_method' => 'realtime',
                'checkin_time' => now(),
                'student_lat' => $lat,
                'student_lng' => $lng,
                'distance_meters' => $distance === null ? null : (int) round($distance),
                'device_uuid' => $deviceUuid,
                'photo_path' => $photoPath,
                'status' => empty($reasons) ? 'auto_approved' : 'flagged',
                'flag_reason' => empty($reasons) ? null : implode(',', $reasons),
            ]);
        } catch (QueryException $e) {
            Storage::disk('public')->delete($photoPath);

            if ($e->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'qr_token' => 'คุณเช็คชื่อกิจกรรมนี้ไปแล้ว',
                ]);
            }

            throw $e;
        }
    }

    /**
     * Self-report check-in: no QR/GPS verification, just a time window and
     * an evidence photo — always lands as flagged since there's nothing
     * automated to vouch for it, an admin must review the photo themselves.
     *
     * @throws ValidationException
     */
    public function selfReportCheckIn(User $user, Activity $activity, UploadedFile $photo): Attendance
    {
        if (! $activity->usesSelfReportCheckIn()) {
            throw ValidationException::withMessages([
                'photo' => __('กิจกรรมนี้ไม่ได้เปิดให้เช็คชื่อแบบรายงานตนเอง'),
            ]);
        }

        if (! $activity->acceptsCheckIn()) {
            throw ValidationException::withMessages([
                'photo' => __('ไม่อยู่ในช่วงเวลาที่เปิดให้เช็คชื่อ'),
            ]);
        }

        if (! $activity->isEligibleFor($user)) {
            throw ValidationException::withMessages([
                'photo' => __('คุณไม่มีสิทธิ์เข้าร่วมกิจกรรมนี้'),
            ]);
        }

        if (Attendance::where('user_id', $user->id)->where('activity_id', $activity->id)->exists()) {
            throw ValidationException::withMessages([
                'photo' => __('คุณเช็คชื่อกิจกรรมนี้ไปแล้ว'),
            ]);
        }

        $photoPath = $photo->store('attendance-selfies', 'public');

        try {
            return Attendance::create([
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'checkin_method' => 'self_report',
                'checkin_time' => now(),
                'photo_path' => $photoPath,
                'status' => 'flagged',
                'flag_reason' => 'SELF_REPORTED',
            ]);
        } catch (QueryException $e) {
            Storage::disk('public')->delete($photoPath);

            if ($e->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'photo' => __('คุณเช็คชื่อกิจกรรมนี้ไปแล้ว'),
                ]);
            }

            throw $e;
        }
    }
}
