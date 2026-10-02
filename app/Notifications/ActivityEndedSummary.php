<?php

namespace App\Notifications;

use App\Models\Activity;

/** Admin (the organiser): an activity just closed — how it went and what's left to review. */
class ActivityEndedSummary extends BaseNotification
{
    public function __construct(private Activity $activity, private int $attended, private int $eligible, private int $toReview)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => $this->toReview > 0 ? 'flag' : 'check',
            'title_key' => 'กิจกรรมจบแล้ว: สรุปผล',
            'body_key' => $this->toReview > 0
                ? 'กิจกรรม ":title" มีผู้เช็คชื่อ :attended จาก :eligible คน ยังรอตรวจอีก :review รายการ'
                : 'กิจกรรม ":title" มีผู้เช็คชื่อ :attended จาก :eligible คน ไม่มีรายการรอตรวจ',
            'body_params' => [
                'title' => $this->activity->title,
                'attended' => $this->attended,
                'eligible' => $this->eligible,
                'review' => $this->toReview,
            ],
            'url' => route('admin.attendance.index', $this->activity),
        ];
    }
}
