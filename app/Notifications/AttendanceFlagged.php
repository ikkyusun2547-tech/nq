<?php

namespace App\Notifications;

use App\Models\Attendance;

class AttendanceFlagged extends BaseNotification
{
    public function __construct(private Attendance $attendance)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        $studentName = $this->attendance->user->name_thai ?? $this->attendance->user->name;

        return [
            'icon' => 'flag',
            'title_key' => 'การเช็คชื่อติดธงแดง',
            'body_key' => ':name เช็คชื่อกิจกรรม ":title" แต่ระบบตรวจพบความผิดปกติ',
            'body_params' => ['name' => $studentName, 'title' => $this->attendance->activity->title],
            'url' => route('admin.attendance.index', $this->attendance->activity),
        ];
    }
}
