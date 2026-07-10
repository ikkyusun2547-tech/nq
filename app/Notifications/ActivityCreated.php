<?php

namespace App\Notifications;

use App\Models\Activity;
use Illuminate\Notifications\Notification;

class ActivityCreated extends Notification
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
            'icon' => 'external',
            'title_key' => 'มีกิจกรรมใหม่ที่คุณมีสิทธิ์เข้าร่วม',
            'body_key' => 'กิจกรรมใหม่ ":title" เปิดรับสมัครแล้ว',
            'body_params' => ['title' => $this->activity->title],
            'url' => route('activities.index'),
        ];
    }
}
