<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Notifications\Channels\FcmChannel;

class ActivityUpdated extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    /**
     * No 'mail' here (unlike BaseNotification::via()) — same reasoning as
     * ActivityCreated::via(): this fans out to every eligible student in a
     * single admin request and can be hundreds for a university-wide
     * activity.
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'flag',
            'title_key' => 'กิจกรรมที่คุณมีสิทธิ์เข้าร่วมมีการอัปเดต',
            'body_key' => 'กิจกรรม ":title" มีการเปลี่ยนแปลงวันเวลา สถานที่ หรือวิธีเช็คชื่อ กรุณาตรวจสอบรายละเอียดอีกครั้ง',
            'body_params' => ['title' => $this->activity->title],
            'url' => route('activities.index'),
        ];
    }
}
