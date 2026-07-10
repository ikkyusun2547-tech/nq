<?php

namespace App\Notifications;

use App\Models\Activity;

class ActivityMissed extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'flag',
            'title_key' => 'คุณพลาดกิจกรรมนี้',
            'body_key' => 'กิจกรรม ":title" ปิดรับเช็กชื่อแล้วและคุณไม่ได้เช็กชื่อเข้าร่วม หากเข้าร่วมจริงสามารถยื่นคำร้องขอเช็กชื่อย้อนหลังได้',
            'body_params' => ['title' => $this->activity->title],
            'url' => route('late-checkin.show', $this->activity),
        ];
    }
}
