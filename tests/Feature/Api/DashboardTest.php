<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

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
            'summary' => ['total_activities', 'required_activities', 'total_hours', 'required_hours', 'category_hours', 'is_cleared'],
            'approved',
            'pending',
            'rejected',
        ]);
        $this->assertSame(1, $response->json('summary.total_activities'));
    }

    public function test_admin_is_blocked_from_the_student_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/dashboard')->assertStatus(403);
    }
}
