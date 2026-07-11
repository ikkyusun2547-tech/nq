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

class SelfCheckInTest extends TestCase
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

    public function test_it_creates_a_flagged_attendance_for_self_report_activities(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        $activity = Activity::factory()->selfReport()->create(['status' => 'open']);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/activities/{$activity->id}/self-checkin", [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'status' => 'flagged',
            'flag_reason' => 'SELF_REPORTED',
        ]);
    }

    public function test_it_rejects_non_self_report_activities(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        $activity = Activity::factory()->create(['status' => 'open', 'checkin_method' => 'realtime']);

        Sanctum::actingAs($user);

        $this->postJson("/api/activities/{$activity->id}/self-checkin", [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertStatus(404);
    }
}
