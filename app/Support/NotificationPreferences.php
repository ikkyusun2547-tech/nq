<?php

namespace App\Support;

use App\Models\User;
use App\Notifications;

/**
 * What a user chose on "ตั้งค่าการแจ้งเตือน": per category, whether to get a
 * push and (where the category ever emails) an email, plus quiet hours.
 * The bell always gets everything — nothing important can be switched off
 * entirely, only the interruptions.
 *
 * Stored as JSON in users.notification_preferences; a missing key means the
 * default (on), so new categories start enabled for everyone.
 */
class NotificationPreferences
{
    public const QUIET_FROM = 22;   // 22:00
    public const QUIET_UNTIL = 7;   // 07:00

    /**
     * category => [label, description, emails?] per audience.
     * "emails" = the category contains notifications that send email at all,
     * so only those show an email switch.
     */
    public static function categoriesFor(User $user): array
    {
        return $user->isAdmin()
            ? [
                'admin_requests' => [__('คำร้องและข้อความใหม่'), __('นักศึกษาส่งคำร้อง เริ่มบทสนทนา หรือตอบข้อความ'), false],
                'admin_activity' => [__('กิจกรรมที่ดูแล'), __('จะเริ่มใน 1 ชม. · เช็คชื่อติดธง GPS จำนวนมาก · สรุปผลเมื่อจบ'), false],
                'admin_digest' => [__('สรุปงานรอตรวจรายวัน'), __('ทุกเช้า 08:00 เฉพาะวันที่มีงานค้าง'), true],
                'account' => [__('บัญชีของฉัน'), __('เมื่อสิทธิ์หรือสถานะบัญชีของคุณเปลี่ยน'), true],
            ]
            : [
                'activity_news' => [__('กิจกรรมใหม่และการเปลี่ยนแปลง'), __('กิจกรรมใหม่ที่มีสิทธิ์ · มีการแก้ไข · ถูกยกเลิก'), true],
                'checkin_reminders' => [__('เตือนเช็คชื่อ'), __('เริ่มพรุ่งนี้ · เปิดเช็คชื่อแล้ว · ใกล้ปิดส่ง · พลาดกิจกรรม'), false],
                'results' => [__('ผลเช็คชื่อและคำร้อง'), __('อนุมัติ / ไม่อนุมัติ · ผลคำร้อง · ผ่านเกณฑ์แล้ว'), true],
                'messages' => [__('ข้อความและประกาศ'), __('เจ้าหน้าที่ตอบข้อความ · ประกาศจากมหาวิทยาลัย'), true],
                'account' => [__('บัญชีของฉัน'), __('เมื่อสถานะบัญชีของคุณเปลี่ยน เช่น จบการศึกษา'), true],
            ];
    }

    /** Which category a notification belongs to, for this recipient. */
    public static function categoryOf(object $notification, object $notifiable): string
    {
        $isAdmin = $notifiable instanceof User && $notifiable->isAdmin();

        return match ($notification::class) {
            Notifications\ActivityCreated::class,
            Notifications\ActivityUpdated::class,
            Notifications\ActivityCancelled::class => 'activity_news',

            Notifications\ActivityStartingTomorrow::class,
            Notifications\CheckInOpened::class,
            Notifications\CheckInClosingSoon::class,
            Notifications\ActivityMissed::class => 'checkin_reminders',

            Notifications\AttendanceApproved::class,
            Notifications\AttendanceRejected::class,
            Notifications\ExternalActivityRequestReviewed::class,
            Notifications\CreditTransferRequestReviewed::class,
            Notifications\LateCheckInRequestReviewed::class,
            Notifications\StudentCleared::class => 'results',

            Notifications\Announcement::class => 'messages',
            Notifications\ContactMessageReplied::class => $isAdmin ? 'admin_requests' : 'messages',

            Notifications\ExternalActivityRequestSubmitted::class,
            Notifications\CreditTransferRequestSubmitted::class,
            Notifications\ContactThreadStarted::class => 'admin_requests',

            Notifications\ActivityStartingSoon::class,
            Notifications\CheckInFlagSurge::class,
            Notifications\ActivityEndedSummary::class => 'admin_activity',

            Notifications\AdminDailyDigest::class => 'admin_digest',
            Notifications\AccountUpdated::class => 'account',

            default => 'other',
        };
    }

    public static function wants(object $notifiable, string $category, string $channel): bool
    {
        if (! $notifiable instanceof User) {
            return true;
        }

        return (bool) data_get($notifiable->notification_preferences, "$category.$channel", true);
    }

    public static function quietHoursEnabled(object $notifiable): bool
    {
        return $notifiable instanceof User && (bool) data_get($notifiable->notification_preferences, 'quiet_hours', false);
    }

    public static function isQuietNow(): bool
    {
        $hour = (int) now()->format('G');

        return $hour >= self::QUIET_FROM || $hour < self::QUIET_UNTIL;
    }

    /**
     * Normalise the settings form into the stored shape (only known
     * categories / channels, booleans).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function fromForm(User $user, array $input): array
    {
        $prefs = ['quiet_hours' => (bool) ($input['quiet_hours'] ?? false)];

        foreach (self::categoriesFor($user) as $key => [, , $emails]) {
            $prefs[$key] = ['push' => (bool) data_get($input, "$key.push", false)];
            if ($emails) {
                $prefs[$key]['email'] = (bool) data_get($input, "$key.email", false);
            }
        }

        return $prefs;
    }
}
