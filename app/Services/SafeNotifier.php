<?php

namespace App\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Illuminate\Support\Facades\Notification::send() (and a bare ->notify())
 * aborts the instant one recipient's channel throws — e.g. a mail rate
 * limit, a bounced address, an SMTP timeout — and Laravel's own send loop
 * has no try/catch around it. Every notifiable after the failing one in
 * iteration order then gets nothing at all, not even the previously
 * bulletproof 'database' channel, and the exception bubbles up into
 * whatever HTTP request triggered it (an activity/check-in/request that
 * already committed to the database) as an unrelated 500.
 *
 * This isolates each recipient — one failed send can't take out the rest —
 * and a notification failure never breaks the action that triggered it.
 */
class SafeNotifier
{
    /**
     * @param  mixed  $notifiables  A single notifiable model, or any iterable of them.
     */
    public static function send(mixed $notifiables, Notification $notification): void
    {
        $notifiables = is_iterable($notifiables) ? $notifiables : [$notifiables];

        foreach ($notifiables as $notifiable) {
            try {
                $notifiable->notify(clone $notification);
            } catch (Throwable $e) {
                Log::warning('Notification delivery failed', [
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->getKey(),
                    'notification' => get_class($notification),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
