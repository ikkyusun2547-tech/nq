<?php

namespace App\Console\Commands;

use App\Services\ActivityAlerts;
use Illuminate\Console\Command;

/**
 * Time-based activity notifications (see App\Services\ActivityAlerts):
 * check-in opened, self-report closing within the hour, and "starts within
 * the hour" for the organiser. Scheduled every five minutes; each reminder
 * goes out at most once per activity no matter how often this runs.
 */
class SendActivityReminders extends Command
{
    protected $signature = 'app:send-activity-reminders';

    protected $description = 'Send check-in opened / closing soon / starting soon reminders that are due';

    public function handle(ActivityAlerts $alerts): void
    {
        $sent = $alerts->sendDueReminders();

        $this->info("Check-in opened: {$sent['opened']}, closing soon: {$sent['closing']}, starting soon: {$sent['starting_soon']} activity/activities.");
    }
}
