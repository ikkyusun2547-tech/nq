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
     * accepted, to absorb clock skew and scan/upload latency. Selfie capture
     * hands off to the OS camera app rather than an in-page preview, so this
     * covers roughly 2 minutes end-to-end (app switch + shot + GPS + upload).
     */
    private const GRACE_WINDOWS = 7;

    /**
     * Marks a printable/backup token in place of the rotating window number
     * — never expires on its own (acceptsCheckIn() is what eventually shuts
     * it off, same as everything else), so it's identifiable at a glance and
     * can never collide with a real window number (always a positive int).
     */
    private const STATIC_MARKER = 'static';

    public function currentWindow(): int
    {
        return intdiv(time(), self::WINDOW_SECONDS);
    }

    public function generate(Activity $activity, ?int $window = null): string
    {
        $window ??= $this->currentWindow();

        return sprintf('%d.%d.%s', $activity->id, $window, $this->sign($activity, $window));
    }

    /**
     * A printable/backup token for when there's no live screen at the venue
     * (dead projector, no internet for the kiosk page, etc). Unlike the
     * rotating token, this one doesn't expire by itself, which is exactly
     * what makes it printable — but it also means it can't offer the same
     * anti-screenshot-reuse guarantee. Every check-in made with it must be
     * treated as lower-trust by the caller (see resolveActivity()'s
     * $isStatic return value) — never auto-approved.
     */
    public function generateStatic(Activity $activity): string
    {
        return sprintf('%d.%s.%s', $activity->id, self::STATIC_MARKER, $this->signStatic($activity));
    }

    public function secondsUntilNextRotation(): int
    {
        return self::WINDOW_SECONDS - (time() % self::WINDOW_SECONDS);
    }

    /**
     * Total time a scanned token stays valid, from the moment its window
     * started until the grace period runs out.
     */
    public function scanValiditySeconds(): int
    {
        return self::WINDOW_SECONDS * (self::GRACE_WINDOWS + 1);
    }

    /**
     * Parse a scanned token, verify its signature and freshness, and
     * resolve it to the Activity it was issued for.
     *
     * @return array{0: Activity, 1: bool} the activity, and whether the token was the static/printable kind
     * @throws QrTokenException
     */
    public function resolveActivity(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3 || ! ctype_digit($parts[0])) {
            throw new QrTokenException(__('รูปแบบ QR Code ไม่ถูกต้อง'));
        }

        [$activityId, $middle, $signature] = $parts;

        $activity = Activity::find((int) $activityId);

        if (! $activity) {
            throw new QrTokenException(__('ไม่พบกิจกรรมที่ตรงกับ QR Code นี้'));
        }

        if ($middle === self::STATIC_MARKER) {
            if (! hash_equals($this->signStatic($activity), $signature)) {
                throw new QrTokenException(__('QR Code ไม่ถูกต้อง'));
            }

            return [$activity, true];
        }

        if (! ctype_digit(ltrim($middle, '-'))) {
            throw new QrTokenException(__('รูปแบบ QR Code ไม่ถูกต้อง'));
        }

        if (! $this->verify($activity, (int) $middle, $signature)) {
            throw new QrTokenException(__('QR Code หมดอายุแล้ว กรุณาสแกนใหม่อีกครั้ง'));
        }

        return [$activity, false];
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

    private function signStatic(Activity $activity): string
    {
        return substr(hash_hmac('sha256', "{$activity->id}.".self::STATIC_MARKER, $activity->qr_secret), 0, 24);
    }
}
