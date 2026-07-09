<?php

namespace App\Notifications;

use App\Models\ExternalActivityRequest;
use Illuminate\Notifications\Notification;

class ExternalActivityRequestReviewed extends Notification
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
        $approved = $this->request->status === 'approved';

        return [
            'icon' => $approved ? 'check' : 'reject',
            'title_key' => $approved ? 'คำร้องกิจกรรมภายนอกได้รับการอนุมัติ' : 'คำร้องกิจกรรมภายนอกถูกปฏิเสธ',
            'body_key' => $approved ? 'คำร้อง ":title" ได้รับอนุมัติ :hours ชม.' : 'คำร้อง ":title" ถูกปฏิเสธ: :reason',
            'body_params' => $approved
                ? ['title' => $this->request->title, 'hours' => $this->request->hours_credited]
                : ['title' => $this->request->title, 'reason' => $this->request->reject_reason],
            'url' => route('external-activities.index'),
        ];
    }
}
