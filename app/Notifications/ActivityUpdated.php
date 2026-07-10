<?php

namespace App\Notifications;

use App\Models\Activity;
use Illuminate\Notifications\Notification;

class ActivityUpdated extends Notification
{
    public function __construct(private Activity $activity)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'flag',
            'title_key' => 'กิจกรรมที่คุณมีสิทธิ์เข้าร่วมมีการอัปเดต',
            'body_key' => 'กิจกรรม ":title" มีการเปลี่ยนแปลงวันเวลา สถานที่ หรือวิธีเช็กชื่อ กรุณาตรวจสอบรายละเอียดอีกครั้ง',
            'body_params' => ['title' => $this->activity->title],
            'url' => route('activities.index'),
        ];
    }
}
