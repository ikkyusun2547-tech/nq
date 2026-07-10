<?php

namespace App\Notifications;

use App\Models\CreditTransferRequest;
use Illuminate\Notifications\Notification;

class CreditTransferRequestReviewed extends Notification
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
        $approved = $this->request->status === 'approved';

        return [
            'icon' => $approved ? 'check' : 'reject',
            'title_key' => $approved ? 'คำร้องเทียบโอนชั่วโมงได้รับการอนุมัติ' : 'คำร้องเทียบโอนชั่วโมงถูกปฏิเสธ',
            'body_key' => $approved ? 'คำร้องเทียบโอนชั่วโมงได้รับอนุมัติ :hours ชม.' : 'คำร้องเทียบโอนชั่วโมงถูกปฏิเสธ: :reason',
            'body_params' => $approved
                ? ['hours' => $this->request->hours_credited]
                : ['reason' => $this->request->reject_reason],
            'url' => route('credit-transfers.index'),
        ];
    }
}
