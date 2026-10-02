<?php

namespace App\Notifications;

/** Admin: morning summary of everything waiting for review. */
class AdminDailyDigest extends BaseNotification
{
    /**
     * @param  array<string, int>  $counts  flagged / external / late / credit / messages
     */
    public function __construct(private array $counts, private int $stale)
    {
    }

    public function toDatabase(object $notifiable): array
    {
        $total = array_sum($this->counts);

        return [
            'icon' => 'flag',
            'title_key' => 'สรุปงานรอตรวจวันนี้ :total รายการ',
            'title_params' => ['total' => $total],
            'body_key' => $this->stale > 0
                ? 'เช็คชื่อติดธง :flagged · กิจกรรมภายนอก :external · ย้อนหลัง :late · เทียบโอน :credit · ข้อความ :messages (ค้างเกิน 3 วัน :stale รายการ)'
                : 'เช็คชื่อติดธง :flagged · กิจกรรมภายนอก :external · ย้อนหลัง :late · เทียบโอน :credit · ข้อความ :messages',
            'body_params' => $this->counts + ['stale' => $this->stale],
            'url' => route('admin.dashboard'),
        ];
    }

    protected function sendsMail(): bool
    {
        return true;
    }
}
