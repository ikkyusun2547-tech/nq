<?php

namespace Tests\Feature\Student;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCardCheckInButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_already_checked_in_open_activity_has_no_check_in_button(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
        $done = Activity::factory()->selfReport()->create(['status' => 'open', 'start_at' => now()->subHour(), 'end_at' => now()->addHour()]);
        $todo = Activity::factory()->selfReport()->create(['status' => 'open', 'start_at' => now()->subHour(), 'end_at' => now()->addHour()]);
        Attendance::factory()->create(['user_id' => $student->id, 'activity_id' => $done->id]);

        $this->actingAs($student)->get(route('activities.index', ['status_group' => 'open']))
            ->assertOk()
            ->assertDontSee(route('self-checkin.show', $done), false)
            ->assertSee(route('self-checkin.show', $todo), false);
    }

    public function test_a_missed_closed_activity_offers_a_late_check_in_request(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
        $missed = Activity::factory()->closed()->create(['start_at' => now()->subDays(2), 'end_at' => now()->subDays(2)->addHours(2)]);
        $pending = Activity::factory()->closed()->create(['start_at' => now()->subDays(3), 'end_at' => now()->subDays(3)->addHours(2)]);
        \App\Models\LateCheckInRequest::create([
            'user_id' => $student->id, 'activity_id' => $pending->id, 'reason' => 'x',
            'proof_image_path' => 'late-checkin-proofs/x.jpg', 'status' => 'pending',
        ]);

        $this->actingAs($student)->get(route('activities.index', ['status_group' => 'ended']))
            ->assertOk()
            ->assertSee(route('late-checkin.show', $missed), false)
            ->assertSee('ขอเช็คชื่อย้อนหลัง')
            ->assertSee('รอตรวจคำร้อง');
    }
}
