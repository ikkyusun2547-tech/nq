<?php

namespace App\Notifications;

use App\Models\Activity;

class ActivitySurveyRequested extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'chat',
            'title_key' => 'ประเมินความพึงพอใจกิจกรรม',
            'body_key' => 'กิจกรรม ":title" สิ้นสุดแล้ว ขอเชิญประเมินความพึงพอใจ (ใช้เวลาไม่ถึง 1 นาที และไม่ระบุตัวตน)',
            'body_params' => ['title' => $this->activity->title],
            'url' => route('activity-survey.show', $this->activity),
        ];
    }
}
