<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires the standard Laravel scheduler cron entry on the server
// (`* * * * * php artisan schedule:run`) — without that running, this never
// fires on its own. Hourly is frequent enough that a missed check-in never
// waits much more than an hour to see it was flagged as missed.
Schedule::command('app:close-ended-activities')->hourly();

// Same cron dependency as above. Once a day is enough for a "starts
// tomorrow" reminder — see NotifyUpcomingActivities' class comment for why
// this can't double-notify the same activity even on a daily cadence.
Schedule::command('app:notify-upcoming-activities')->dailyAt('09:00');

// "Check-in is open", "closing within the hour" and "starts within the hour"
// (App\Services\ActivityAlerts). Each fires once per activity, so a short
// interval only makes them timelier, never duplicated.
Schedule::command('app:send-activity-reminders')->everyFiveMinutes()->withoutOverlapping(10);

// Admins' morning summary of everything waiting for review (skipped when
// there's nothing waiting).
Schedule::command('app:send-admin-digest')->dailyAt('08:00')->withoutOverlapping(10);

// Drains the notification queue (see BaseNotification's ShouldQueue) every
// minute. --stop-when-empty exits as soon as the queue is empty instead of
// running forever, which is what lets this piggyback on the scheduler's
// cron entry above instead of needing a separate long-running `queue:work`
// daemon under Supervisor — the right call at this app's traffic volume,
// worth revisiting if notification volume ever grows enough that a minute's
// delivery lag becomes a problem.
// The overlap lock lives in the cache, which is the database here, so it
// survives a restart. If a deploy kills the worker mid-run the lock is never
// released — with Laravel's default 24-hour expiry that silently stopped all
// notifications for a day. The worker exits within 50s, so 2 minutes is a
// safe expiry that recovers on its own (same idea for the reminders above).
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping(2);
