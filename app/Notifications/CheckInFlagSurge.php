<?php

namespace App\Notifications;

use App\Models\Activity;

/**
 * Admin (the organiser): an unusually large share of an activity's QR
 * check-ins are landing as "GPS outside the area" — often the venue's GPS
 * rather than the students, worth a look (and maybe a bulk approve).
 */
class CheckInFlagSurge extends BaseNotification
{
    public function __construct(private Activity $activity, private int $flagged, private int $total)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => 'flag',
            'title_key' => 'เช็คชื่อติดธง GPS จำนวนมาก',
            'body_key' => 'กิจกรรม ":title" มีเช็คชื่อที่ GPS อยู่นอกพื้นที่ :flagged จาก :total คน อาจเป็นปัญหา GPS ของสถานที่ ลองตรวจสอบ',
            'body_params' => [
                'title' => $this->activity->title,
                'flagged' => $this->flagged,
                'total' => $this->total,
            ],
            'url' => route('admin.attendance.index', ['activity' => $this->activity, 'status' => 'flagged']),
        ];
    }
}
