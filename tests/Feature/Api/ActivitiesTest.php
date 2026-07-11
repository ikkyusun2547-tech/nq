<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Faculty;
use App\Models\User;
use App\Services\AcademicYearCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivitiesTest extends TestCase
{
    use RefreshDatabase;

    private function studentUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student',
            'email' => 'student@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ], $overrides));
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/activities')->assertStatus(401);
    }

    public function test_it_lists_open_activities_for_the_current_academic_year(): void
    {
        $user = $this->studentUser();
        Activity::factory()->create(['status' => 'open', 'academic_year' => AcademicYearCalculator::forDate(now())]);
        Activity::factory()->create(['status' => 'draft', 'academic_year' => AcademicYearCalculator::forDate(now())]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/activities');

        $response->assertOk()->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'checked_in_activity_ids',
            'late_checkin_statuses',
            'faculties',
            'academic_years',
            'filters',
        ]);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_it_filters_by_status_group(): void
    {
        $user = $this->studentUser();
        $year = AcademicYearCalculator::forDate(now());
        Activity::factory()->create(['status' => 'closed', 'academic_year' => $year]);
        Activity::factory()->create(['status' => 'open', 'academic_year' => $year]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/activities?status_group=ended');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('closed', $response->json('data.0.status'));
    }
}
