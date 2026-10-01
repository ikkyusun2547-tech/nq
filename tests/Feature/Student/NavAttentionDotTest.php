<?php

namespace Tests\Feature\Student;

use App\Models\ContactThread;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\ExternalActivityRequestReviewed;
use App\Notifications\LateCheckInRequestReviewed;
use App\Services\StudentAttention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NavAttentionDotTest extends TestCase
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

    private function unreadNotification(User $user, string $type): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'data' => ['title_key' => 'x', 'body_key' => 'y', 'url' => '/'],
        ]);
    }

    public function test_no_dot_when_nothing_is_waiting(): void
    {
        $this->actingAs($this->student())->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('มีรายการใหม่');
    }

    public function test_a_reviewed_request_shows_a_dot_until_the_requests_page_is_opened(): void
    {
        $student = $this->student();
        $this->unreadNotification($student, ExternalActivityRequestReviewed::class);

        $this->assertSame(1, StudentAttention::counts($student)['requests']);
        $this->actingAs($student)->get(route('dashboard'))->assertSee('มีรายการใหม่');

        $this->actingAs($student)->get(route('hour-requests.index'))->assertOk();

        $this->assertSame(0, StudentAttention::counts($student)['requests']);
        $this->actingAs($student)->get(route('dashboard'))->assertDontSee('มีรายการใหม่');
    }

    public function test_a_late_check_in_result_clears_when_the_ended_tab_is_opened(): void
    {
        $student = $this->student();
        $this->unreadNotification($student, LateCheckInRequestReviewed::class);

        $this->assertSame(1, StudentAttention::counts($student)['activities']);

        $this->actingAs($student)->get(route('activities.index', ['status_group' => 'open']))->assertOk();
        $this->assertSame(1, StudentAttention::counts($student)['activities']);

        $this->actingAs($student)->get(route('activities.index', ['status_group' => 'ended']))->assertOk();
        $this->assertSame(0, StudentAttention::counts($student)['activities']);
    }

    public function test_an_unread_admin_reply_shows_a_dot_on_contact(): void
    {
        $student = $this->student();
        ContactThread::create([
            'user_id' => $student->id, 'subject' => 'Q', 'status' => 'open',
            'last_message_at' => now(), 'student_unread' => true, 'admin_unread' => false,
        ]);

        $this->assertSame(1, StudentAttention::counts($student)['contact']);
        $this->actingAs($student)->get(route('dashboard'))->assertSee('มีรายการใหม่');
    }
}
