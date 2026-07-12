<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use App\Notifications\ActivityMissed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CloseEndedActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_closes_an_activity_whose_end_time_has_passed_and_notifies_missing_students(): void
    {
        Notification::fake();

        $activity = Activity::factory()->create([
            'status' => 'open',
            'start_at' => now()->subHours(3),
            'end_at' => now()->subHour(),
        ]);
        $checkedIn = User::factory()->create(['role' => 'student', 'email' => 'checked@srru.ac.th']);
        $missing = User::factory()->create(['role' => 'student', 'email' => 'missing@srru.ac.th']);
        Attendance::factory()->for($checkedIn)->for($activity)->create(['status' => 'auto_approved']);

        $this->artisan('app:close-ended-activities')->assertSuccessful();

        $this->assertSame('closed', $activity->fresh()->status);
        Notification::assertSentTo($missing, ActivityMissed::class);
        Notification::assertNotSentTo($checkedIn, ActivityMissed::class);
    }

    public function test_it_leaves_activities_that_have_not_ended_yet_alone(): void
    {
        $activity = Activity::factory()->create([
            'status' => 'open',
            'start_at' => now(),
            'end_at' => now()->addHour(),
        ]);

        $this->artisan('app:close-ended-activities')->assertSuccessful();

        $this->assertSame('open', $activity->fresh()->status);
    }

    public function test_it_leaves_already_closed_or_cancelled_activities_alone(): void
    {
        $closed = Activity::factory()->create(['status' => 'closed', 'end_at' => now()->subDay()]);
        $cancelled = Activity::factory()->create(['status' => 'cancelled', 'end_at' => now()->subDay()]);
        $draft = Activity::factory()->create(['status' => 'draft', 'end_at' => now()->subDay()]);

        $this->artisan('app:close-ended-activities')->assertSuccessful();

        $this->assertSame('closed', $closed->fresh()->status);
        $this->assertSame('cancelled', $cancelled->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);
    }
}
