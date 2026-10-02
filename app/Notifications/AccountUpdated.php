<?php

namespace App\Notifications;

/** Any user: an admin changed their role or account status. */
class AccountUpdated extends BaseNotification
{
    /** change => [title, body] */
    private const MESSAGES = [
        'promoted' => ['บัญชีของคุณได้รับสิทธิ์แอดมิน', 'คุณสามารถจัดการกิจกรรมและตรวจคำร้องได้แล้ว'],
        'demoted' => ['สิทธิ์แอดมินของคุณถูกยกเลิก', 'บัญชีของคุณกลับเป็นบัญชีนักศึกษา'],
        'banned' => ['บัญชีของคุณถูกระงับการใช้งาน', 'หากคิดว่าเกิดข้อผิดพลาด กรุณาติดต่อกองพัฒนานักศึกษา'],
        'unbanned' => ['บัญชีของคุณใช้งานได้อีกครั้ง', 'เจ้าหน้าที่ปลดการระงับบัญชีของคุณแล้ว'],
        'graduated' => ['บัญชีของคุณถูกบันทึกว่าจบการศึกษา', 'ยังเข้าดูประวัติและดาวน์โหลดใบสรุปกิจกรรมได้ตามปกติ'],
        'ungraduated' => ['สถานะจบการศึกษาของคุณถูกยกเลิก', 'บัญชีของคุณกลับเป็นนักศึกษาปัจจุบัน'],
    ];

    public function __construct(private string $change)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        [$title, $body] = self::MESSAGES[$this->change] ?? ['บัญชีของคุณมีการเปลี่ยนแปลง', ''];

        return [
            'icon' => in_array($this->change, ['banned', 'demoted'], true) ? 'flag' : 'check',
            'title_key' => $title,
            'body_key' => $body,
            'url' => url('/'),
        ];
    }

    protected function sendsMail(): bool
    {
        return true;
    }
}
