<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Notifications\Notification;

class AttendanceFlagged extends Notification
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
        $studentName = $this->attendance->user->name_thai ?? $this->attendance->user->name;

        return [
            'icon' => 'flag',
            'title_key' => 'การเช็กชื่อติดธงแดง',
            'body_key' => ':name เช็กชื่อกิจกรรม ":title" แต่ระบบตรวจพบความผิดปกติ',
            'body_params' => ['name' => $studentName, 'title' => $this->attendance->activity->title],
            'url' => route('admin.attendance.index', $this->attendance->activity),
        ];
    }
}
