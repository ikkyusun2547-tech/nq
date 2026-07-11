<?php

namespace Tests\Feature\Api;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FacultyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_majors_for_a_faculty(): void
    {
        $user = User::factory()->create(['role' => 'student', 'email' => 'student@srru.ac.th']);
        $faculty = Faculty::factory()->create();
        Major::factory()->count(2)->for($faculty)->create();
        Major::factory()->for(Faculty::factory())->create();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/faculty-majors/{$faculty->id}");

        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
