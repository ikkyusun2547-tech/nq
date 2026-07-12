<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;

/**
 * The only admin-initiated (not automatically triggered by a system event)
 * notification in the app — see Admin\AnnouncementController. subject/body
 * are free text an admin typed, not translation-catalog keys like every
 * other notification's title_key/body_key, so this skips BaseNotification's
 * __($data['title_key']) lookup and just passes them through as-is.
 */
class Announcement extends BaseNotification
{
    public function __construct(private string $subject, private string $body)
    {
    }

    /**
     * No 'mail' here (unlike BaseNotification::via()) — an announcement can
     * fan out to hundreds of students in a single request, and this app's
     * notifications send synchronously (not queued). SMTP is by far the
     * slowest and most rate-limit-prone channel of the three (Mailtrap's
     * free tier throttles to roughly 1/sec), so at any real recipient count
     * it blows past PHP's execution time limit before the loop finishes.
     * database + FCM push are both fast enough to stay synchronous; the
     * in-app bell is also the primary surface for this app regardless.
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'flag',
            'title_key' => $this->subject,
            'body_key' => $this->body,
            'url' => route('dashboard'),
        ];
    }
}
