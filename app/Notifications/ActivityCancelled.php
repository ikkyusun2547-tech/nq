<?php

namespace App\Notifications;

use App\Models\Activity;

/** Student: an activity they're eligible for was cancelled. Emailed too, since plans may change around it. */
class ActivityCancelled extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'reject',
            'title_key' => 'กิจกรรมถูกยกเลิก',
            'body_key' => 'กิจกรรม ":title" วันที่ :date ถูกยกเลิกแล้ว',
            'body_params' => [
                'title' => $this->activity->title,
                'date' => $this->activity->start_at->translatedFormat('d M Y H:i'),
            ],
            'url' => route('activities.show', $this->activity),
        ];
    }

    protected function sendsMail(): bool
    {
        return true;
    }
}
