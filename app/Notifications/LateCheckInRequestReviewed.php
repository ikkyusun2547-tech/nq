<?php

namespace App\Notifications;

use App\Models\LateCheckInRequest;

class LateCheckInRequestReviewed extends BaseNotification
{
    public function __construct(private LateCheckInRequest $request)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        $approved = $this->request->status === 'approved';

        return [
            'icon' => $approved ? 'check' : 'reject',
            'title_key' => $approved ? 'คำร้องขอเช็กชื่อย้อนหลังได้รับการอนุมัติ' : 'คำร้องขอเช็กชื่อย้อนหลังถูกปฏิเสธ',
            'body_key' => $approved ? 'คำร้องเช็กชื่อย้อนหลัง ":title" ได้รับอนุมัติ :hours ชม.' : 'คำร้องเช็กชื่อย้อนหลัง ":title" ถูกปฏิเสธ: :reason',
            'body_params' => $approved
                ? ['title' => $this->request->activity->title, 'hours' => $this->request->hours_credited]
                : ['title' => $this->request->activity->title, 'reason' => $this->request->reject_reason],
            'url' => route('activities.index', ['status_group' => 'ended']),
        ];
    }
}
