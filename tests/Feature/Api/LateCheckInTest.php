<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LateCheckInTest extends TestCase
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

    public function test_show_requires_a_closed_activity(): void
    {
        $user = $this->studentUser();
        $activity = Activity::factory()->create(['status' => 'open']);

        Sanctum::actingAs($user);

        $this->getJson("/api/activities/{$activity->id}/late-checkin")->assertStatus(404);
    }

    public function test_it_submits_a_late_checkin_request(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        $activity = Activity::factory()->closed()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/activities/{$activity->id}/late-checkin", [
            'reason' => 'ลืมสแกน QR',
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('late_check_in_requests', [
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'status' => 'pending',
        ]);
    }

    public function test_it_rejects_a_second_unresolved_request(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        $activity = Activity::factory()->closed()->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/activities/{$activity->id}/late-checkin", [
            'reason' => 'ลืมสแกน QR',
            'proof_image' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertOk();

        $this->postJson("/api/activities/{$activity->id}/late-checkin", [
            'reason' => 'อีกครั้ง',
            'proof_image' => UploadedFile::fake()->image('proof2.jpg'),
        ])->assertStatus(422);
    }
}
