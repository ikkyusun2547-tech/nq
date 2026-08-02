<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\CreditTransferRequest;
use App\Models\Faculty;
use App\Models\User;
use App\Services\AcademicYearCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function completeStudent(string $email): User
    {
        return User::factory()->create([
            'role' => 'student',
            'email' => $email,
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_it_requires_a_completed_profile(): void
    {
        $user = User::factory()->create(['role' => 'student', 'email' => 'incomplete@srru.ac.th', 'faculty_id' => null]);
        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')
            ->assertStatus(409)
            ->assertJson(['error_code' => 'PROFILE_INCOMPLETE']);
    }

    public function test_it_returns_summary_and_feed_for_a_student(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'complete@srru.ac.th',
            'faculty_id' => \App\Models\Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
        $activity = Activity::factory()->create();
        Attendance::factory()->for($user)->for($activity)->create(['status' => 'auto_approved']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk()->assertJsonStructure([
            'summary' => ['total_activities', 'required_activities', 'total_hours', 'required_hours', 'category_hours', 'hours_by_source', 'is_cleared'],
            'approved',
            'pending',
            'rejected',
        ]);
        $this->assertSame(1, $response->json('summary.total_activities'));
    }

    public function test_summary_includes_the_current_position_label_for_an_approved_current_year_claim(): void
    {
        $user = $this->completeStudent('withposition@srru.ac.th');
        CreditTransferRequest::create([
            'user_id' => $user->id,
            'position' => 'class_leader',
            'academic_year' => AcademicYearCalculator::forDate(now()),
            'hours_requested' => 50,
            'activity_category' => 'volunteer',
            'status' => 'approved',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertSame('หัวหน้าหมู่เรียน', $response->json('summary.current_position_label'));
    }

    public function test_summary_current_position_label_is_null_without_a_current_year_claim(): void
    {
        $user = $this->completeStudent('noposition@srru.ac.th');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertNull($response->json('summary.current_position_label'));
    }

    public function test_admin_is_blocked_from_the_student_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/dashboard')->assertStatus(403);
    }
}
