<?php

namespace App\Notifications;

use App\Models\ExternalActivityRequest;
use Illuminate\Notifications\Notification;

class ExternalActivityRequestSubmitted extends Notification
{
    public function __construct(private ExternalActivityRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $studentName = $this->request->user->name_thai ?? $this->request->user->name;

        return [
            'icon' => 'external',
            'title_key' => 'คำร้องกิจกรรมภายนอกใหม่',
            'body_key' => ':name ส่งคำร้อง ":title" รอตรวจสอบ',
            'body_params' => ['name' => $studentName, 'title' => $this->request->title],
            'url' => route('admin.external-activities.index'),
        ];
    }
}
