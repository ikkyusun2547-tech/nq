<?php

namespace App\Notifications;

use App\Models\Activity;

/** Admin (the organiser): their activity starts within the hour — time to put the QR up. */
class ActivityStartingSoon extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        $selfReport = $this->activity->usesSelfReportCheckIn();

        return [
            'icon' => 'clock',
            'title_key' => 'กิจกรรมจะเริ่มในอีกไม่ถึง 1 ชั่วโมง',
            'body_key' => $selfReport
                ? 'กิจกรรม ":title" เริ่มเวลา :time น. (เช็คชื่อแบบแนบหลักฐาน)'
                : 'กิจกรรม ":title" เริ่มเวลา :time น. อย่าลืมเปิดหน้าแสดง QR ที่หน้างาน',
            'body_params' => [
                'title' => $this->activity->title,
                'time' => $this->activity->start_at->format('H:i'),
            ],
            'url' => route('admin.attendance.index', $this->activity),
        ];
    }
}
