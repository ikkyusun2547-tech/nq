<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\ContactThread;
use App\Models\CreditTransferRequest;
use App\Models\ExternalActivityRequest;
use App\Models\LateCheckInRequest;
use App\Models\User;
use App\Notifications\AdminDailyDigest;
use App\Services\AdminReviewInbox;
use App\Services\SafeNotifier;
use Illuminate\Console\Command;

/**
 * Morning summary for every active admin of what's waiting for review,
 * including how much has been waiting more than three days. Flagged
 * check-ins and late requests deliberately don't notify one by one (the
 * nav dots show them), so this is where they get a push. Nothing is sent on
 * a day with an empty queue.
 */
class SendAdminDigest extends Command
{
    public const STALE_DAYS = 3;

    protected $signature = 'app:send-admin-digest';

    protected $description = 'Send admins a summary of everything waiting for review';

    public function handle(): void
    {
        $counts = AdminReviewInbox::counts() + [
            'messages' => ContactThread::where('admin_unread', true)->count(),
        ];

        if (array_sum($counts) === 0) {
            $this->info('Nothing waiting — no digest sent.');

            return;
        }

        $before = now()->subDays(self::STALE_DAYS);
        $stale = Attendance::where('status', 'flagged')->where('checkin_time', '<', $before)->count()
            + ExternalActivityRequest::where('status', 'pending')->where('created_at', '<', $before)->count()
            + LateCheckInRequest::where('status', 'pending')->where('created_at', '<', $before)->count()
            + CreditTransferRequest::where('status', 'pending')->where('created_at', '<', $before)->count();

        $admins = User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->where(fn ($q) => $q->whereNull('account_status')->orWhere('account_status', '!=', 'banned'))
            ->get();

        SafeNotifier::send($admins, new AdminDailyDigest($counts, $stale));

        $this->info('Digest sent to '.$admins->count().' admin(s): '.array_sum($counts).' waiting, '.$stale.' stale.');
    }
}
