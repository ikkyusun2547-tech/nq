<?php

namespace App\Notifications;

use App\Models\LateCheckInRequest;

class LateCheckInRequestSubmitted extends BaseNotification
{
    public function __construct(private LateCheckInRequest $request)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        $studentName = $this->request->user->name_thai ?? $this->request->user->name;

        return [
            'icon' => 'external',
            'title_key' => 'คำร้องขอเช็กชื่อย้อนหลังใหม่',
            'body_key' => ':name ขอเช็กชื่อย้อนหลังกิจกรรม ":title" รอตรวจสอบ',
            'body_params' => ['name' => $studentName, 'title' => $this->request->activity->title],
            'url' => route('admin.late-checkins.index'),
        ];
    }
}
