<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Notifications\ActivityMissed;
use App\Services\SafeNotifier;
use Illuminate\Console\Command;

/**
 * Admin\ActivityController::update() already closes an activity and notifies
 * missing students the moment an admin manually flips its status — but
 * nothing did that automatically once an activity's end_at simply passed,
 * so students who missed one only got prompted to submit a late check-in
 * request if an admin happened to remember to close it by hand. Scheduled
 * (see routes/console.php) to run this instead, hourly.
 */
class CloseEndedActivities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:close-ended-activities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close activities whose end time has passed and notify students who never checked in';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $activities = Activity::whereIn('status', ['open', 'ongoing', 'full'])
            ->where('end_at', '<', now())
            ->get();

        foreach ($activities as $activity) {
            $activity->update(['status' => 'closed']);

            $missing = $activity->missingStudentsQuery()->get();
            if ($missing->isNotEmpty()) {
                SafeNotifier::send($missing, new ActivityMissed($activity));
            }
        }

        $this->info("Closed {$activities->count()} activity/activities.");
    }
}
