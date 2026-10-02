<?php

namespace Tests\Feature\Student;

use App\Models\Activity;
use App\Models\ActivityRestriction;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityFeedOrderTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
    }

    private function activity(string $title, string $status, int $startsInDays): Activity
    {
        return Activity::factory()->create([
            'title' => $title,
            'status' => $status,
            'start_at' => now()->addDays($startsInDays),
            'end_at' => now()->addDays($startsInDays)->addHours(3),
        ]);
    }

    public function test_the_main_feed_puts_actionable_activities_first(): void
    {
        $student = $this->student();

        $attended = $this->activity('Attended Activity', 'open', 0);
        Attendance::factory()->create(['user_id' => $student->id, 'activity_id' => $attended->id]);
        $this->activity('General Upcoming', 'draft', 2);
        $targeted = $this->activity('Targeted Upcoming', 'draft', 5);
        ActivityRestriction::create(['activity_id' => $targeted->id, 'target_year' => 2]);
        $this->activity('Open Later', 'open', 4);
        $this->activity('Open Soon', 'open', 3);
        // "Ongoing" is no longer a stored status: an open activity that is happening right now.
        Activity::factory()->create(['title' => 'Ongoing Now', 'status' => 'open', 'start_at' => now()->subHour(), 'end_at' => now()->addHours(2)]);

        $this->actingAs($student)->get(route('activities.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Ongoing Now',
                'Open Soon',
                'Open Later',
                'Targeted Upcoming',
                'General Upcoming',
                'Attended Activity',
            ]);
    }

    public function test_ended_activities_come_last_most_recent_first_and_cancelled_ones_are_hidden(): void
    {
        $student = $this->student();
        $this->activity('Older Closed Activity', 'closed', -5);
        $this->activity('Overdue Open Activity', 'open', -2);
        $this->activity('Cancelled Activity', 'cancelled', 2);
        $attended = $this->activity('Attended Activity', 'open', 1);
        Attendance::factory()->create(['user_id' => $student->id, 'activity_id' => $attended->id]);
        $this->activity('Open Activity', 'open', 3);

        $this->actingAs($student)->get(route('activities.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Open Activity',
                'Attended Activity',
                'Overdue Open Activity',
                'Older Closed Activity',
            ])
            ->assertDontSee('Cancelled Activity');
    }
}
