<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\LateCheckInRequest;
use App\Models\User;
use App\Notifications\LateCheckInRequestReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LateCheckInApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    private function pendingRequest(array $overrides = []): LateCheckInRequest
    {
        $activity = $overrides['activity_id'] ?? Activity::factory()->closed()->create()->id;
        unset($overrides['activity_id']);

        return LateCheckInRequest::create(array_merge([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'stu'.uniqid().'@srru.ac.th'])->id,
            'activity_id' => $activity,
            'reason' => 'ลืมสแกน QR',
            'proof_image_path' => 'late-checkins/fake.jpg',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_a_student_cannot_approve_a_late_checkin_request(): void
    {
        $request = $this->pendingRequest();
        $student = User::factory()->create(['role' => 'student', 'email' => 'other@srru.ac.th']);

        $this->actingAs($student)
            ->post(route('admin.late-checkins.approve', $request))
            ->assertForbidden();
    }

    public function test_it_approves_a_pending_request_creating_an_attendance_row(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $request = $this->pendingRequest();

        $response = $this->actingAs($admin)->post(route('admin.late-checkins.approve', $request));

        $response->assertRedirect();
        $this->assertDatabaseHas('late_check_in_requests', ['id' => $request->id, 'status' => 'approved']);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $request->user_id,
            'activity_id' => $request->activity_id,
            'checkin_method' => 'late_request',
            'status' => 'auto_approved',
            'reviewed_by' => $admin->id,
        ]);
        Notification::assertSentTo($request->user, LateCheckInRequestReviewed::class);
    }

    public function test_it_stores_an_hours_override_only_when_it_differs_from_the_activitys_normal_credit_hours(): void
    {
        $admin = $this->admin();
        $activity = Activity::factory()->closed()->create(['credit_hours' => 5]);
        $request = $this->pendingRequest(['activity_id' => $activity->id]);

        $this->actingAs($admin)->post(route('admin.late-checkins.approve', $request), [
            'hours_credited' => 3,
        ]);

        $this->assertDatabaseHas('late_check_in_requests', ['id' => $request->id, 'hours_approved' => 3]);
        $this->assertDatabaseHas('attendances', ['user_id' => $request->user_id, 'credited_hours' => 3]);
    }

    public function test_approving_when_the_student_already_has_an_attendance_for_that_activity_fails_gracefully(): void
    {
        $admin = $this->admin();
        $request = $this->pendingRequest();
        Attendance::factory()->create(['user_id' => $request->user_id, 'activity_id' => $request->activity_id]);

        $response = $this->actingAs($admin)->post(route('admin.late-checkins.approve', $request));

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('late_check_in_requests', ['id' => $request->id, 'status' => 'pending']);
    }

    public function test_approving_an_already_resolved_request_is_rejected(): void
    {
        $request = $this->pendingRequest(['status' => 'approved']);

        $this->actingAs($this->admin())
            ->post(route('admin.late-checkins.approve', $request))
            ->assertStatus(422);
    }

    public function test_it_rejects_a_pending_request_with_a_reason(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $request = $this->pendingRequest();

        $response = $this->actingAs($admin)->post(route('admin.late-checkins.reject', $request), [
            'reject_reason' => 'หลักฐานไม่ชัดเจนว่าเข้าร่วมกิจกรรมนี้จริง',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('late_check_in_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reject_reason' => 'หลักฐานไม่ชัดเจนว่าเข้าร่วมกิจกรรมนี้จริง',
        ]);
        $this->assertDatabaseMissing('attendances', ['user_id' => $request->user_id, 'activity_id' => $request->activity_id]);
        Notification::assertSentTo($request->user, LateCheckInRequestReviewed::class);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs($this->admin())
            ->post(route('admin.late-checkins.reject', $request), [])
            ->assertSessionHasErrors('reject_reason');
    }
}
