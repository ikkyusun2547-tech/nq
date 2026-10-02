<?php

namespace App\Notifications;

use App\Models\Activity;

/** Student: a self-report window closes within the hour and they haven't sent their evidence yet. */
class CheckInClosingSoon extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'clock',
            'title_key' => 'ใกล้ปิดส่งหลักฐานเช็คชื่อ',
            'body_key' => 'กิจกรรม ":title" จะปิดรับหลักฐานเวลา :time น. คุณยังไม่ได้ส่ง',
            'body_params' => [
                'title' => $this->activity->title,
                'time' => $this->activity->checkin_closes_at?->format('H:i'),
            ],
            'url' => route('self-checkin.show', $this->activity),
        ];
    }
}
