<?php

namespace Tests\Feature\Student;

use App\Models\Activity;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInRequirementsTest extends TestCase
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

    public function test_it_reports_when_an_activity_requires_gps(): void
    {
        $activity = Activity::factory()->create(['checkin_method' => 'realtime', 'requires_gps' => true]);

        $this->actingAs($this->student())
            ->getJson(route('activities.checkin-requirements', $activity))
            ->assertOk()
            ->assertJson(['requires_gps' => true]);
    }

    public function test_it_reports_when_an_activity_does_not_require_gps(): void
    {
        $activity = Activity::factory()->create(['checkin_method' => 'realtime', 'requires_gps' => false]);

        $this->actingAs($this->student())
            ->getJson(route('activities.checkin-requirements', $activity))
            ->assertOk()
            ->assertJson(['requires_gps' => false]);
    }
}
