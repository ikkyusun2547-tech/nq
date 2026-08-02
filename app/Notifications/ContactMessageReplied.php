<?php

namespace App\Notifications;

use App\Models\ContactMessage;

/**
 * One class covers both directions of a reply — admin replying to a
 * student, or a student replying back on an existing thread — since
 * toDatabase() already receives the recipient and can tell which side
 * they're on, instead of two near-identical notification classes.
 */
class ContactMessageReplied extends BaseNotification
{
    public function __construct(private ContactMessage $message)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        $thread = $this->message->thread;

        if ($notifiable->id === $thread->user_id) {
            return [
                'icon' => 'chat',
                'title_key' => 'เจ้าหน้าที่ตอบกลับข้อความของคุณ',
                'body_key' => 'มีการตอบกลับข้อความ ":subject"',
                'body_params' => ['subject' => $thread->subject],
                'url' => route('contact.show', $thread),
            ];
        }

        $senderName = $this->message->sender->name_thai ?? $this->message->sender->name;

        return [
            'icon' => 'chat',
            'title_key' => 'มีข้อความใหม่จากนักศึกษา',
            'body_key' => ':name ตอบกลับข้อความ ":subject"',
            'body_params' => ['name' => $senderName, 'subject' => $thread->subject],
            'url' => route('admin.contact.show', $thread),
        ];
    }
}
