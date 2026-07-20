<?php

namespace Tests\Feature\Admin;

use App\Models\ExternalActivityRequest;
use App\Models\User;
use App\Notifications\ExternalActivityRequestReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExternalApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    private function pendingRequest(array $overrides = []): ExternalActivityRequest
    {
        return ExternalActivityRequest::create(array_merge([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'stu'.uniqid().'@srru.ac.th'])->id,
            'title' => 'ค่ายอาสาพัฒนาชุมชน',
            'organization' => 'มูลนิธิทดสอบ',
            'activity_date' => now()->subDays(3)->toDateString(),
            'activity_category' => 'volunteer',
            'hours_requested' => 10,
            'proof_image_path' => 'external-activities/fake.jpg',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_a_student_cannot_approve_an_external_activity_request(): void
    {
        $request = $this->pendingRequest();
        $student = User::factory()->create(['role' => 'student', 'email' => 'other@srru.ac.th']);

        $this->actingAs($student)
            ->post(route('admin.external-activities.approve', $request))
            ->assertForbidden();
    }

    public function test_it_approves_a_pending_request_and_notifies_the_student(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $request = $this->pendingRequest();

        $response = $this->actingAs($admin)->post(route('admin.external-activities.approve', $request));

        $response->assertRedirect();
        $this->assertDatabaseHas('external_activity_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'hours_approved' => null, // no override -> credited as requested
            'reviewed_by' => $admin->id,
        ]);
        Notification::assertSentTo($request->user, ExternalActivityRequestReviewed::class);
    }

    public function test_it_stores_an_hours_override_only_when_it_differs_from_the_requested_amount(): void
    {
        $admin = $this->admin();
        $request = $this->pendingRequest(['hours_requested' => 10]);

        $this->actingAs($admin)->post(route('admin.external-activities.approve', $request), [
            'hours_approved' => 6,
        ]);

        $this->assertDatabaseHas('external_activity_requests', ['id' => $request->id, 'hours_approved' => 6]);
    }

    public function test_approving_an_already_resolved_request_is_rejected(): void
    {
        $request = $this->pendingRequest(['status' => 'rejected']);

        $this->actingAs($this->admin())
            ->post(route('admin.external-activities.approve', $request))
            ->assertStatus(422);
    }

    public function test_it_rejects_a_pending_request_with_a_reason(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $request = $this->pendingRequest();

        $response = $this->actingAs($admin)->post(route('admin.external-activities.reject', $request), [
            'reject_reason' => 'รูปเกียรติบัตรไม่ชัดเจน',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('external_activity_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reject_reason' => 'รูปเกียรติบัตรไม่ชัดเจน',
        ]);
        Notification::assertSentTo($request->user, ExternalActivityRequestReviewed::class);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs($this->admin())
            ->post(route('admin.external-activities.reject', $request), [])
            ->assertSessionHasErrors('reject_reason');
    }

    public function test_the_index_filters_by_category(): void
    {
        $volunteer = $this->pendingRequest(['activity_category' => 'volunteer']);
        $academic = $this->pendingRequest(['activity_category' => 'academic']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.external-activities.index', ['activity_category' => 'academic']));

        $response->assertOk();
        $ids = $response->viewData('requests')->pluck('id');
        $this->assertTrue($ids->contains($academic->id));
        $this->assertFalse($ids->contains($volunteer->id));
    }
}
