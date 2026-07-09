<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Notifications\Notification;

class AttendanceApproved extends Notification
{
    public function __construct(private Attendance $attendance)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'check',
            'title_key' => 'การเช็กชื่อของคุณได้รับการอนุมัติแล้ว',
            'body_key' => 'เจ้าหน้าที่ตรวจสอบและอนุมัติการเช็กชื่อกิจกรรม ":title" ให้คุณแล้ว',
            'body_params' => ['title' => $this->attendance->activity->title],
            'url' => route('dashboard'),
        ];
    }
}
