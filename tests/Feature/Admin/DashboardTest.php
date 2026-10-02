<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\CreditTransferRequest;
use App\Models\ExternalActivityRequest;
use App\Models\Faculty;
use App\Models\LateCheckInRequest;
use App\Models\User;
use App\Services\AdminReviewInbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    private function student(string $name): User
    {
        return User::factory()->create([
            'role' => 'student',
            'name_thai' => $name,
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => (string) random_int(10000000000, 99999999999),
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
    }

    private function seedPendingWork(): void
    {
        $activity = Activity::factory()->closed()->create(['title' => 'Closed Event']);

        Attendance::factory()->create(['user_id' => $this->student('Flagged Student')->id, 'activity_id' => Activity::factory()->create()->id, 'status' => 'flagged', 'flag_reason' => 'GPS_OUT_OF_BOUNDS']);
        ExternalActivityRequest::create(['user_id' => $this->student('External Student')->id, 'title' => 'Outside Seminar', 'organization' => 'Some Org', 'activity_date' => now()->subWeek(), 'activity_category' => 'academic', 'hours_requested' => 6, 'proof_image_path' => 'proofs/a.jpg', 'status' => 'pending']);
        LateCheckInRequest::create(['user_id' => $this->student('Late Student')->id, 'activity_id' => $activity->id, 'reason' => 'Phone died', 'proof_image_path' => 'proofs/c.jpg', 'status' => 'pending']);
        CreditTransferRequest::create(['user_id' => $this->student('Credit Student')->id, 'position' => 'class_leader', 'academic_year' => 2569, 'hours_requested' => 50, 'activity_category' => 'volunteer', 'status' => 'pending']);
        // Already-reviewed work must not show up.
        ExternalActivityRequest::create(['user_id' => $this->student('Done Student')->id, 'title' => 'Done Request', 'organization' => 'X', 'activity_date' => now()->subWeek(), 'activity_category' => 'academic', 'hours_requested' => 3, 'proof_image_path' => 'proofs/b.jpg', 'status' => 'approved']);
    }

    public function test_the_inbox_merges_every_pending_review_type(): void
    {
        $this->seedPendingWork();

        $inbox = app(AdminReviewInbox::class)->build();

        $this->assertSame(['flagged' => 1, 'external' => 1, 'late' => 1, 'credit' => 1], $inbox['counts']);
        $this->assertEqualsCanonicalizing(['flagged', 'external', 'late', 'credit'], $inbox['items']->pluck('type')->all());
        $this->assertNotContains('Done Request', $inbox['items']->pluck('title')->all());
    }

    public function test_the_dashboard_renders_the_inbox_and_todays_activities(): void
    {
        $this->seedPendingWork();
        Activity::factory()->create(['title' => 'Today Event', 'status' => 'open', 'start_at' => now()->startOfDay()->addHours(9), 'end_at' => now()->endOfDay()->subHour()]);
        Activity::factory()->create(['title' => 'Cancelled Today', 'status' => 'cancelled', 'start_at' => now()->startOfDay()->addHours(9), 'end_at' => now()->endOfDay()->subHour()]);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Flagged Student')
            ->assertSee('Outside Seminar')
            ->assertSee('Late Student')
            ->assertSee('Credit Student')
            ->assertSee('Today Event')
            ->assertDontSee('Cancelled Today')
            ->assertSee(route('admin.external-activities.index'));
    }

    public function test_an_empty_inbox_says_so(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('เคลียร์ครบแล้ว'));
    }
}
