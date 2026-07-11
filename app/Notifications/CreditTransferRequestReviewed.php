<?php

namespace App\Notifications;

use App\Models\CreditTransferRequest;

class CreditTransferRequestReviewed extends BaseNotification
{
    public function __construct(private CreditTransferRequest $request)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        $approved = $this->request->status === 'approved';

        return [
            'icon' => $approved ? 'check' : 'reject',
            'title_key' => $approved ? 'คำร้องเทียบโอนชั่วโมงได้รับการอนุมัติ' : 'คำร้องเทียบโอนชั่วโมงถูกปฏิเสธ',
            'body_key' => $approved ? 'คำร้องเทียบโอนชั่วโมงได้รับอนุมัติ :hours ชม.' : 'คำร้องเทียบโอนชั่วโมงถูกปฏิเสธ: :reason',
            'body_params' => $approved
                ? ['hours' => $this->request->hours_credited]
                : ['reason' => $this->request->reject_reason],
            'url' => route('hour-requests.index', ['tab' => 'credit']),
        ];
    }
}
