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
        $this->activity('Full Activity', 'full', 1);
        $this->activity('General Upcoming', 'draft', 2);
        $targeted = $this->activity('Targeted Upcoming', 'draft', 5);
        ActivityRestriction::create(['activity_id' => $targeted->id, 'target_year' => 2]);
        $this->activity('Open Later', 'open', 4);
        $this->activity('Open Soon', 'open', 3);
        $this->activity('Ongoing Now', 'ongoing', 0);

        $this->actingAs($student)->get(route('activities.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Ongoing Now',
                'Open Soon',
                'Open Later',
                'Targeted Upcoming',
                'General Upcoming',
                'Full Activity',
                'Attended Activity',
            ]);
    }

    public function test_the_main_feed_leaves_out_ended_cancelled_and_overdue_activities(): void
    {
        $student = $this->student();
        $this->activity('Closed Activity', 'closed', -3);
        $this->activity('Cancelled Activity', 'cancelled', 2);
        $this->activity('Overdue Open Activity', 'open', -2);
        $this->activity('Visible Activity', 'open', 1);

        $this->actingAs($student)->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Visible Activity')
            ->assertDontSee('Closed Activity')
            ->assertDontSee('Cancelled Activity')
            ->assertDontSee('Overdue Open Activity');
    }
}
