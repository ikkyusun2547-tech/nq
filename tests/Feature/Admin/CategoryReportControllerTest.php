<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sums_approved_hours_across_every_student_by_category(): void
    {
        $studentA = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        $studentB = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th']);
        $activity = Activity::factory()->create(['activity_category' => 'sports', 'credit_hours' => 10]);

        Attendance::factory()->for($activity)->for($studentA)->create(['status' => 'auto_approved']);
        Attendance::factory()->for($activity)->for($studentB)->create(['status' => 'auto_approved']);

        $breakdown = app(ActivityEvaluationService::class)->universityCategoryBreakdown();

        $this->assertSame(20, $breakdown['sports']);
    }

    public function test_a_student_cannot_view_the_report(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.reports.category'))->assertForbidden();
    }

    public function test_an_admin_can_view_the_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.reports.category'))->assertOk();
    }
}
