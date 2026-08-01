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

    public function test_it_sorts_by_year_level_via_a_join_on_users(): void
    {
        $senior = User::factory()->create(['role' => 'student', 'email' => 'senior@srru.ac.th', 'year_level' => 4]);
        $junior = User::factory()->create(['role' => 'student', 'email' => 'junior@srru.ac.th', 'year_level' => 1]);
        $this->pendingRequest(['user_id' => $senior->id]);
        $this->pendingRequest(['user_id' => $junior->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.external-activities.index', ['status' => 'all', 'sort' => 'year_level', 'dir' => 'asc']));

        $response->assertOk();
        $years = $response->viewData('requests')->pluck('user.year_level')->all();

        $this->assertSame([1, 4], $years);
    }

    public function test_it_sorts_by_hours_requested_ascending(): void
    {
        $this->pendingRequest(['hours_requested' => 10]);
        $this->pendingRequest(['hours_requested' => 2]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.external-activities.index', ['status' => 'all', 'sort' => 'hours_requested', 'dir' => 'asc']));

        $response->assertOk();
        $hours = $response->viewData('requests')->pluck('hours_requested')->all();

        $this->assertSame([2, 10], $hours);
    }

    public function test_it_sorts_by_student_name_via_a_join_on_users(): void
    {
        $zebra = User::factory()->create(['role' => 'student', 'email' => 'zebra@srru.ac.th', 'name_thai' => 'ฮ นักศึกษาท้ายสุด']);
        $alpha = User::factory()->create(['role' => 'student', 'email' => 'alpha@srru.ac.th', 'name_thai' => 'ก นักศึกษาแรกสุด']);
        $this->pendingRequest(['user_id' => $zebra->id]);
        $this->pendingRequest(['user_id' => $alpha->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.external-activities.index', ['status' => 'all', 'sort' => 'name', 'dir' => 'asc']));

        $response->assertOk();
        $names = $response->viewData('requests')->pluck('user.name_thai')->all();

        $this->assertSame(['ก นักศึกษาแรกสุด', 'ฮ นักศึกษาท้ายสุด'], $names);
    }

    public function test_it_sorts_by_title_ascending(): void
    {
        $this->pendingRequest(['title' => 'Zebra Camp']);
        $this->pendingRequest(['title' => 'Alpha Camp']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.external-activities.index', ['status' => 'all', 'sort' => 'title', 'dir' => 'asc']));

        $response->assertOk();
        $titles = $response->viewData('requests')->pluck('title')->all();

        $this->assertSame(['Alpha Camp', 'Zebra Camp'], $titles);
    }
}
