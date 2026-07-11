<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Every concrete notification in this app already builds a
 * {icon, title_key, body_key, body_params, url} array for the in-app
 * notification bell via toDatabase(). Reusing that same array here means
 * every notification gets an email and a push notification for free, with
 * identical wording, instead of duplicating a toMail()/toFcm() in every
 * concrete class.
 */
abstract class BaseNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database', 'mail', FcmChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toDatabase($notifiable);
        $name = $notifiable->name_thai ?? $notifiable->name;

        return (new MailMessage)
            ->subject('[SRRU Check] '.__($data['title_key']))
            ->greeting(__('สวัสดีคุณ :name', ['name' => $name]))
            ->line(__($data['body_key'], $data['body_params'] ?? []))
            ->when(isset($data['url']), fn (MailMessage $mail) => $mail->action(__('ดูรายละเอียด'), $data['url']))
            ->salutation(__('ระบบเช็กชื่อกิจกรรมนักศึกษา SRRU'));
    }

    /**
     * @return array{title: string, body: string, data: array<string, mixed>}
     */
    public function toFcm(object $notifiable): array
    {
        $data = $this->toDatabase($notifiable);

        return [
            'title' => __($data['title_key']),
            'body' => __($data['body_key'], $data['body_params'] ?? []),
            'data' => ['icon' => $data['icon'] ?? null, 'url' => $data['url'] ?? null],
        ];
    }

    /**
     * @return array{icon: string, title_key: string, body_key: string, body_params?: array<string, mixed>, url?: string}
     */
    abstract public function toDatabase(object $notifiable): array;
}
