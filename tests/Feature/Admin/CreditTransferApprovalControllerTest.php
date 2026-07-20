<?php

namespace Tests\Feature\Admin;

use App\Models\CreditTransferRequest;
use App\Models\User;
use App\Notifications\CreditTransferRequestReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CreditTransferApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    private function pendingRequest(array $overrides = []): CreditTransferRequest
    {
        return CreditTransferRequest::create(array_merge([
            'user_id' => User::factory()->create(['role' => 'student', 'email' => 'stu'.uniqid().'@srru.ac.th'])->id,
            'position' => 'class_leader',
            'academic_year' => 2568,
            'hours_requested' => 50,
            'proof_image_path' => 'credit-transfers/fake.jpg',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_a_student_cannot_approve_a_credit_transfer_request(): void
    {
        $request = $this->pendingRequest();
        $student = User::factory()->create(['role' => 'student', 'email' => 'other@srru.ac.th']);

        $this->actingAs($student)
            ->post(route('admin.credit-transfers.approve', $request), ['activity_category' => 'culture'])
            ->assertForbidden();
    }

    public function test_it_approves_a_pending_request_and_notifies_the_student(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $request = $this->pendingRequest();

        $response = $this->actingAs($admin)->post(route('admin.credit-transfers.approve', $request), [
            'activity_category' => 'culture',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('credit_transfer_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'activity_category' => 'culture',
            'hours_approved' => null, // unchanged from the requested amount -> no override stored
            'reviewed_by' => $admin->id,
        ]);
        Notification::assertSentTo($request->user, CreditTransferRequestReviewed::class);
    }

    public function test_it_stores_an_hours_override_only_when_it_differs_from_the_requested_amount(): void
    {
        $admin = $this->admin();
        $request = $this->pendingRequest(['hours_requested' => 50]);

        $this->actingAs($admin)->post(route('admin.credit-transfers.approve', $request), [
            'activity_category' => 'culture',
            'hours_approved' => 30,
        ]);

        $this->assertDatabaseHas('credit_transfer_requests', ['id' => $request->id, 'hours_approved' => 30]);
    }

    public function test_approving_requires_a_valid_activity_category(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs($this->admin())
            ->post(route('admin.credit-transfers.approve', $request), ['activity_category' => 'not-a-real-category'])
            ->assertSessionHasErrors('activity_category');
    }

    public function test_approving_an_already_resolved_request_is_rejected(): void
    {
        $request = $this->pendingRequest(['status' => 'approved']);

        $this->actingAs($this->admin())
            ->post(route('admin.credit-transfers.approve', $request), ['activity_category' => 'culture'])
            ->assertStatus(422);
    }

    public function test_it_rejects_a_pending_request_with_a_reason(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $request = $this->pendingRequest();

        $response = $this->actingAs($admin)->post(route('admin.credit-transfers.reject', $request), [
            'reject_reason' => 'ไม่พบหลักฐานคำสั่งแต่งตั้งที่ชัดเจน',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('credit_transfer_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reject_reason' => 'ไม่พบหลักฐานคำสั่งแต่งตั้งที่ชัดเจน',
        ]);
        Notification::assertSentTo($request->user, CreditTransferRequestReviewed::class);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs($this->admin())
            ->post(route('admin.credit-transfers.reject', $request), [])
            ->assertSessionHasErrors('reject_reason');
    }

    public function test_the_index_reports_tab_counts_independent_of_filters(): void
    {
        $this->pendingRequest();
        $this->pendingRequest(['status' => 'approved']);
        $this->pendingRequest(['status' => 'rejected']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.credit-transfers.index', ['search' => 'no-such-student']));

        $response->assertOk();
        $tabCounts = $response->viewData('tabCounts');
        $this->assertSame(1, (int) $tabCounts['pending']);
        $this->assertSame(1, (int) $tabCounts['approved']);
        $this->assertSame(1, (int) $tabCounts['rejected']);
        $this->assertSame(3, (int) $tabCounts['all']);
    }
}
