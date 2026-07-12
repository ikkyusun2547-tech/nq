<?php

namespace App\Notifications;

use App\Models\Attendance;

class AttendanceRejected extends BaseNotification
{
    public function __construct(private Attendance $attendance)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'reject',
            'title_key' => 'การเช็คชื่อของคุณถูกปฏิเสธ',
            'body_key' => 'เจ้าหน้าที่ตรวจสอบการเช็คชื่อกิจกรรม ":title" แล้วปฏิเสธ: :reason',
            'body_params' => [
                'title' => $this->attendance->activity->title,
                'reason' => $this->attendance->reject_reason,
            ],
            'url' => route('dashboard'),
        ];
    }
}
