<?php

namespace App\Notifications;

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

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'flag',
            'title_key' => $this->subject,
            'body_key' => $this->body,
            'url' => route('dashboard'),
        ];
    }

    protected function sendsMail(): bool
    {
        return true;
    }
}
