<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Notifications\ActivityStartingTomorrow;
use App\Services\SafeNotifier;
use Illuminate\Console\Command;

/**
 * Reminds eligible students the day *before* an activity they can check
 * into actually happens — CloseEndedActivities' ActivityMissed only fires
 * after the fact (too late to attend by then), this is the earlier
 * heads-up. Scheduled (see routes/console.php) to run once daily; the
 * whereBetween window below only ever matches a given activity on the one
 * calendar day that counts as "tomorrow" for it, so a daily run can't
 * double-notify the same activity even if it never misses a day.
 */
class NotifyUpcomingActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-upcoming-activities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind eligible students about activities starting tomorrow';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tomorrow = now()->addDay();

        $activities = Activity::whereIn('status', ['open', 'ongoing'])
            ->whereBetween('start_at', [$tomorrow->copy()->startOfDay(), $tomorrow->copy()->endOfDay()])
            ->get();

        $notified = 0;
        foreach ($activities as $activity) {
            $students = $activity->eligibleStudentsQuery()->get();

            if ($students->isNotEmpty()) {
                SafeNotifier::send($students, new ActivityStartingTomorrow($activity));
                $notified++;
            }
        }

        $this->info("Reminded students about {$notified} of {$activities->count()} activity/activities starting tomorrow.");
    }
}
