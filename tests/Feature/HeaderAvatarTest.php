<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ], $attributes));
    }

    public function test_the_header_shows_the_google_photo_when_there_is_one(): void
    {
        $student = $this->student(['avatar_url' => 'https://lh3.googleusercontent.com/a/example-photo']);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('src="https://lh3.googleusercontent.com/a/example-photo"', false)
            ->assertSee('referrerpolicy="no-referrer"', false);
    }

    public function test_the_header_falls_back_to_initials_without_a_photo(): void
    {
        $student = $this->student(['avatar_url' => null]);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('googleusercontent.com', false);
    }
}
