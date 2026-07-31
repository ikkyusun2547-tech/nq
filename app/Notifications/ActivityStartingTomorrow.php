<?php

namespace App\Notifications;

use App\Models\Activity;

class ActivityStartingTomorrow extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'external',
            'title_key' => 'กิจกรรมที่คุณมีสิทธิ์เข้าร่วมจะเริ่มพรุ่งนี้',
            'body_key' => 'กิจกรรม ":title" จะเริ่มวันพรุ่งนี้ เวลา :time ที่ :location',
            'body_params' => [
                'title' => $this->activity->title,
                'time' => $this->activity->start_at->translatedFormat('d M Y H:i'),
                'location' => $this->activity->location_name,
            ],
            'url' => route('activities.index'),
        ];
    }
}
