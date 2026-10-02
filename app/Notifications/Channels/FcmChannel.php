<?php

namespace App\Notifications\Channels;

use App\Models\DeviceToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Firebase\Messaging\RegistrationTokens;
use Throwable;

/**
 * Every concrete notification already builds a uniform toDatabase() array
 * via BaseNotification; toFcm() (added there) reuses it, so this channel
 * just needs to fan it out to every device token the notifiable has
 * registered — same never-break-the-request philosophy as SafeNotifier.
 */
class FcmChannel
{
    /**
     * Firebase is resolved here, inside the try below, rather than injected:
     * a missing or broken service-account key used to throw while the
     * container built this channel — outside any catch — so the whole queued
     * job failed instead of just skipping the push.
     */
    private function messaging(): Messaging
    {
        return app(Messaging::class);
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $tokens = $notifiable->deviceTokens()->pluck('token');
        if ($tokens->isEmpty()) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        $message = CloudMessage::new()
            ->withNotification(FcmNotification::create($payload['title'], $payload['body']))
            ->withData(array_map('strval', array_filter($payload['data'] ?? [])));

        try {
            $report = $this->messaging()->sendMulticast(
                $message,
                RegistrationTokens::fromValue($tokens->all()),
            );
        } catch (Throwable $e) {
            Log::warning('FCM send failed: '.$e->getMessage());

            return;
        }

        $stale = [...$report->invalidTokens(), ...$report->unknownTokens()];
        if ($stale !== []) {
            DeviceToken::whereIn('token', $stale)->delete();
        }
    }
}
