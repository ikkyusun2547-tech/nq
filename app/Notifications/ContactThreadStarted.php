<?php

namespace App\Notifications;

use App\Models\ContactThread;

class ContactThreadStarted extends BaseNotification
{
    public function __construct(private ContactThread $thread)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        $studentName = $this->thread->student->name_thai ?? $this->thread->student->name;

        return [
            'icon' => 'chat',
            'title_key' => 'มีข้อความใหม่จากนักศึกษา',
            'body_key' => ':name ส่งข้อความ ":subject"',
            'body_params' => ['name' => $studentName, 'subject' => $this->thread->subject],
            'url' => route('admin.contact.show', $this->thread),
        ];
    }
}
