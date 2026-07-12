<?php

namespace Tests\Feature\Api;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExternalActivityTest extends TestCase
{
    use RefreshDatabase;

    private function studentUser(): User
    {
        return User::factory()->create([
            'role' => 'student',
            'email' => 'student@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
    }

    public function test_it_creates_a_pending_request(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/external-activities', [
            'title' => 'ค่ายอาสา',
            'organization' => 'มูลนิธิตัวอย่าง',
            'activity_date' => now()->subDay()->toDateString(),
            'activity_category' => 'volunteer',
            'hours_requested' => 5,
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('external_activity_requests', [
            'user_id' => $user->id,
            'title' => 'ค่ายอาสา',
            'status' => 'pending',
        ]);
    }

    public function test_it_accepts_a_pdf_as_proof(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/external-activities', [
            'title' => 'ค่ายอาสา',
            'organization' => 'มูลนิธิตัวอย่าง',
            'activity_date' => now()->subDay()->toDateString(),
            'activity_category' => 'volunteer',
            'hours_requested' => 5,
            'proof_image' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('external_activity_requests', [
            'user_id' => $user->id,
            'title' => 'ค่ายอาสา',
            'status' => 'pending',
        ]);
    }

    public function test_it_enforces_the_annual_hour_cap(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/external-activities', [
            'title' => 'ค่ายอาสา',
            'organization' => 'มูลนิธิตัวอย่าง',
            'activity_date' => now()->subDay()->toDateString(),
            'activity_category' => 'volunteer',
            'hours_requested' => 999,
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('hours_requested');
    }

    public function test_index_returns_the_students_own_requests(): void
    {
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/external-activities');

        $response->assertOk()->assertJsonStructure(['data', 'meta', 'current_academic_year', 'hours_remaining']);
    }
}
