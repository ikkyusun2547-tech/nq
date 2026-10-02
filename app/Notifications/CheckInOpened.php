<?php

namespace App\Notifications;

use App\Models\Activity;

/** Student: check-in for an activity they're eligible for (and haven't done) just opened. */
class CheckInOpened extends BaseNotification
{
    public function __construct(private Activity $activity)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        $selfReport = $this->activity->usesSelfReportCheckIn();

        return [
            'icon' => 'clock',
            'title_key' => $selfReport ? 'เปิดให้ส่งหลักฐานเช็คชื่อแล้ว' : 'เปิดเช็คชื่อแล้ว',
            'body_key' => $selfReport
                ? 'กิจกรรม ":title" เปิดให้แนบรูปหลักฐานแล้ว ส่งได้ถึง :until'
                : 'กิจกรรม ":title" เริ่มแล้ว สแกน QR ที่หน้างานเพื่อเช็คชื่อได้เลย',
            'body_params' => [
                'title' => $this->activity->title,
                'until' => $this->activity->checkin_closes_at?->translatedFormat('d M H:i'),
            ],
            'url' => $selfReport ? route('self-checkin.show', $this->activity) : route('activities.show', $this->activity),
        ];
    }
}
