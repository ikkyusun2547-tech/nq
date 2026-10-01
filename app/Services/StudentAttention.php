<?php

namespace App\Services;

use App\Models\ContactThread;
use App\Models\User;
use App\Notifications\CreditTransferRequestReviewed;
use App\Notifications\ExternalActivityRequestReviewed;
use App\Notifications\LateCheckInRequestReviewed;

/**
 * Drives the red dots in the student nav: a reviewed request the student
 * hasn't looked at yet, or an admin reply they haven't read. "Seen" reuses
 * what's already stored — the bell's unread notifications and
 * contact_threads.student_unread — and visiting the page that shows the
 * result clears it (see markSeen()).
 */
class StudentAttention
{
    /** Reviewed-request notifications grouped by the nav item that shows their result. */
    public const NOTIFICATION_TYPES = [
        'requests' => [ExternalActivityRequestReviewed::class, CreditTransferRequestReviewed::class],
        'activities' => [LateCheckInRequestReviewed::class],
    ];

    /**
     * @return array{requests: int, activities: int, contact: int}
     */
    public static function counts(User $user): array
    {
        $unread = $user->unreadNotifications()
            ->whereIn('type', array_merge(...array_values(self::NOTIFICATION_TYPES)))
            ->pluck('type');

        return [
            'requests' => $unread->intersect(self::NOTIFICATION_TYPES['requests'])->count(),
            'activities' => $unread->intersect(self::NOTIFICATION_TYPES['activities'])->count(),
            'contact' => ContactThread::where('user_id', $user->id)->where('student_unread', true)->count(),
        ];
    }

    /**
     * @param  'requests'|'activities'  $group
     */
    public static function markSeen(User $user, string $group): void
    {
        $user->unreadNotifications()
            ->whereIn('type', self::NOTIFICATION_TYPES[$group])
            ->update(['read_at' => now()]);
    }
}
