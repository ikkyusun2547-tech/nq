<?php

namespace App\Notifications;

use App\Models\CreditTransferRequest;
use Illuminate\Notifications\Notification;

class CreditTransferRequestSubmitted extends Notification
{
    public function __construct(private CreditTransferRequest $request)
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
            'icon' => 'credit',
            'title_key' => 'คำร้องเทียบโอนชั่วโมงใหม่',
            'body_key' => ':name ส่งคำร้องเทียบโอนชั่วโมงจากตำแหน่ง รอตรวจสอบ',
            'body_params' => ['name' => $studentName],
            'url' => route('admin.credit-transfers.index'),
        ];
    }
}
