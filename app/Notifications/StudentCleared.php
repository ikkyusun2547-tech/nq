<?php

namespace App\Notifications;

/** Student: they've now met the activity criteria for graduation. Sent once (users.cleared_notified_at). */
class StudentCleared extends BaseNotification
{
    public function __construct(private int $hours, private int $activities)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'check',
            'title_key' => 'ยินดีด้วย คุณผ่านเกณฑ์กิจกรรมแล้ว',
            'body_key' => 'สะสมครบ :activities กิจกรรม / :hours ชั่วโมง ตามเกณฑ์ ดาวน์โหลดใบสรุปกิจกรรมได้ที่หน้าแรก',
            'body_params' => ['hours' => $this->hours, 'activities' => $this->activities],
            'url' => route('dashboard'),
        ];
    }

    protected function sendsMail(): bool
    {
        return true;
    }
}
