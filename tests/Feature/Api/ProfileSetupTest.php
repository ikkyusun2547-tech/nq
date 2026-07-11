<?php

namespace Tests\Feature\Api;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_faculties_with_nested_majors(): void
    {
        $user = User::factory()->create(['role' => 'student', 'email' => 'student@srru.ac.th']);
        $faculty = Faculty::factory()->create();
        Major::factory()->for($faculty)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/setup-profile');

        $response->assertOk()->assertJsonCount(1, 'faculties');
        $this->assertCount(1, $response->json('faculties.0.majors'));
    }

    public function test_it_saves_profile_and_marks_it_complete(): void
    {
        $user = User::factory()->create(['role' => 'student', 'email' => 'student@srru.ac.th']);
        $faculty = Faculty::factory()->create();
        $major = Major::factory()->for($faculty)->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/setup-profile', [
            'title_prefix' => 'นาย',
            'first_name' => 'สมชาย',
            'last_name' => 'ศรีวรรณ',
            'student_id' => '65123456701',
            'enrollment_year' => 2565,
            'year_level' => 2,
            'program_type' => 'normal',
            'faculty_id' => $faculty->id,
            'major_id' => $major->id,
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('user.profile_completed'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'student_id' => '65123456701']);
    }

    public function test_it_rejects_a_major_that_does_not_belong_to_the_chosen_faculty(): void
    {
        $user = User::factory()->create(['role' => 'student', 'email' => 'student@srru.ac.th']);
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $majorOfB = Major::factory()->for($facultyB)->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/setup-profile', [
            'title_prefix' => 'นาย',
            'first_name' => 'สมชาย',
            'last_name' => 'ศรีวรรณ',
            'student_id' => '65123456701',
            'enrollment_year' => 2565,
            'year_level' => 2,
            'program_type' => 'normal',
            'faculty_id' => $facultyA->id,
            'major_id' => $majorOfB->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('major_id');
    }
}
