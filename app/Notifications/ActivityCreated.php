<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Notifications\Channels\FcmChannel;

class ActivityCreated extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    /**
     * No 'mail' here (unlike BaseNotification::via()) — this fans out to
     * every eligible student in a single admin request (see
     * Admin\ActivityController::notifyEligibleStudentsOfNewActivity), which
     * can be hundreds for a university-wide activity. Same reasoning as
     * Announcement::via(): synchronous SMTP at that volume blows past PHP's
     * execution time limit before the loop finishes.
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
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
