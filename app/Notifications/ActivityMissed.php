<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Notifications\Channels\FcmChannel;

class ActivityMissed extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    /**
     * No 'mail' here (unlike BaseNotification::via()) — same reasoning as
     * ActivityCreated::via(): this fans out to every student who missed the
     * activity in a single admin request (see
     * Admin\ActivityController::notifyMissingStudents) and can be hundreds
     * for a university-wide activity.
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'flag',
            'title_key' => 'คุณพลาดกิจกรรมนี้',
            'body_key' => 'กิจกรรม ":title" ปิดรับเช็คชื่อแล้วและคุณไม่ได้เช็คชื่อเข้าร่วม หากเข้าร่วมจริงสามารถยื่นคำร้องขอเช็คชื่อย้อนหลังได้',
            'body_params' => ['title' => $this->activity->title],
            'url' => route('late-checkin.show', $this->activity),
        ];
    }
}
