<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Every concrete notification in this app already builds a
 * {icon, title_key, body_key, body_params, url} array for the in-app
 * notification bell via toDatabase(). Reusing that same array here means
 * every notification gets an email and a push notification for free, with
 * identical wording, instead of duplicating a toMail()/toFcm() in every
 * concrete class.
 *
 * ShouldQueue: dispatches to the `jobs` table (QUEUE_CONNECTION=database)
 * instead of sending inline within the request — required so a bulk
 * fan-out (see ActivityCreated/ActivityUpdated/ActivityMissed/Announcement,
 * which can each target hundreds of students from one admin action) can't
 * block that request on synchronous SMTP. Draining the queue is handled by
 * a scheduled `queue:work --stop-when-empty` in routes/console.php, piggybacking
 * on the same cron entry the scheduler already needs.
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Laravel's built-in mail channel (unlike FcmChannel/SafeNotifier) has
     * no try/catch around its own send — an SMTP failure throws and aborts
     * whatever channels haven't run yet in this attempt, and by default
     * the queued job then retries from scratch. A retry re-runs *every*
     * channel again, including 'database' — which isn't idempotent — so a
     * single flaky mail send previously produced duplicate rows in the
     * notification bell (and duplicate push notifications) every time it
     * was retried. Capping to one attempt means a mail failure just drops
     * the email instead of ever re-running the channels that already
     * succeeded.
     */
    public int $tries = 1;

    /**
     * 'mail' goes last specifically so its fragility can never prevent
     * 'database' (the bell) or FcmChannel (push) from running first —
     * both of those already fail safe (FcmChannel catches its own
     * exceptions; SafeNotifier isolates failures per recipient), so mail
     * is the only channel here that can abort its own attempt.
     */
    public function via(object $notifiable): array
    {
        return $this->sendsMail()
            ? ['database', FcmChannel::class, 'mail']
            : ['database', FcmChannel::class];
    }

    /**
     * Every notification lands on the bell and as a push; email is kept for
     * the ones worth a trace in the inbox (request outcomes, announcements,
     * cancellations) so a busy week doesn't bury students in mail.
     * Override to opt in.
     */
    protected function sendsMail(): bool
    {
        return false;
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
            ->salutation(__('ระบบเช็คชื่อกิจกรรมนักศึกษา SRRU'));
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
