<?php

namespace App\Services;

use App\Exceptions\QrTokenException;
use App\Models\Activity;

class DynamicQrTokenGenerator
{
    /**
     * QR tokens rotate every 15 seconds per institutional policy.
     */
    public const WINDOW_SECONDS = 15;

    /**
     * How many past windows (in addition to the current one) are still
     * accepted, to absorb clock skew and scan/upload latency.
     */
    private const GRACE_WINDOWS = 1;

    public function currentWindow(): int
    {
        return intdiv(time(), self::WINDOW_SECONDS);
    }

    public function generate(Activity $activity, ?int $window = null): string
    {
        $window ??= $this->currentWindow();

        return sprintf('%d.%d.%s', $activity->id, $window, $this->sign($activity, $window));
    }

    public function secondsUntilNextRotation(): int
    {
        return self::WINDOW_SECONDS - (time() % self::WINDOW_SECONDS);
    }

    /**
     * Parse a scanned token, verify its signature and freshness, and
     * resolve it to the Activity it was issued for.
     *
     * @throws QrTokenException
     */
    public function resolveActivity(string $token): Activity
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3 || ! ctype_digit($parts[0]) || ! ctype_digit(ltrim($parts[1], '-'))) {
            throw new QrTokenException(__('รูปแบบ QR Code ไม่ถูกต้อง'));
        }

        [$activityId, $window, $signature] = $parts;

        $activity = Activity::find((int) $activityId);

        if (! $activity) {
            throw new QrTokenException(__('ไม่พบกิจกรรมที่ตรงกับ QR Code นี้'));
        }

        if (! $this->verify($activity, (int) $window, $signature)) {
            throw new QrTokenException(__('QR Code หมดอายุแล้ว กรุณาสแกนใหม่อีกครั้ง'));
        }

        return $activity;
    }

    private function verify(Activity $activity, int $window, string $signature): bool
    {
        $currentWindow = $this->currentWindow();

        for ($i = 0; $i <= self::GRACE_WINDOWS; $i++) {
            $candidateWindow = $currentWindow - $i;

            if ($window === $candidateWindow && hash_equals($this->sign($activity, $candidateWindow), $signature)) {
                return true;
            }
        }

        return false;
    }

    private function sign(Activity $activity, int $window): string
    {
        return substr(hash_hmac('sha256', "{$activity->id}.{$window}", $activity->qr_secret), 0, 24);
    }
}
