<?php

namespace Tests\Feature\Admin;

use App\Models\CreditTransferPosition;
use App\Models\CreditTransferRequest;
use App\Models\User;
use App\Notifications\CreditTransferRequestReviewed;
use App\Services\AcademicYearCalculator;
use App\Services\ActivityEvaluationService;
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

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email' => 'super@srru.ac.th']);
    }

    private function student(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
        ], $overrides));
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
            ->post(route('admin.credit-transfers.approve', $request))
            ->assertForbidden();
    }

    public function test_it_approves_a_pending_request_and_notifies_the_student(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $request = $this->pendingRequest();

        // Position credits are approved without picking a category.
        $response = $this->actingAs($admin)->post(route('admin.credit-transfers.approve', $request));

        $response->assertRedirect();
        $this->assertDatabaseHas('credit_transfer_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'activity_category' => null,
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
            'hours_approved' => 30,
        ]);

        $this->assertDatabaseHas('credit_transfer_requests', ['id' => $request->id, 'hours_approved' => 30]);
    }

    public function test_approving_an_already_resolved_request_is_rejected(): void
    {
        $request = $this->pendingRequest(['status' => 'approved']);

        $this->actingAs($this->admin())
            ->post(route('admin.credit-transfers.approve', $request))
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

    public function test_it_sorts_by_academic_year_descending(): void
    {
        $this->pendingRequest(['academic_year' => 2566]);
        $this->pendingRequest(['academic_year' => 2568]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.credit-transfers.index', ['sort' => 'academic_year', 'dir' => 'desc']));

        $response->assertOk();
        $years = $response->viewData('requests')->pluck('academic_year')->all();

        $this->assertSame([2568, 2566], $years);
    }

    public function test_it_sorts_by_student_name_via_a_join_on_users(): void
    {
        $zebra = User::factory()->create(['role' => 'student', 'email' => 'zebra@srru.ac.th', 'name_thai' => 'ฮ นักศึกษาท้ายสุด']);
        $alpha = User::factory()->create(['role' => 'student', 'email' => 'alpha@srru.ac.th', 'name_thai' => 'ก นักศึกษาแรกสุด']);
        $this->pendingRequest(['user_id' => $zebra->id]);
        $this->pendingRequest(['user_id' => $alpha->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.credit-transfers.index', ['sort' => 'name', 'dir' => 'asc']));

        $response->assertOk();
        $names = $response->viewData('requests')->pluck('user.name_thai')->all();

        $this->assertSame(['ก นักศึกษาแรกสุด', 'ฮ นักศึกษาท้ายสุด'], $names);
    }

    // --- grant() ---

    public function test_super_admin_grants_credit_transfer_hours_directly_to_a_student(): void
    {
        Notification::fake();
        $student = $this->student();
        $year = AcademicYearCalculator::forDate(now());

        $response = $this->actingAs($superAdmin = $this->superAdmin())
            ->post(route('admin.credit-transfers.grant', $student), [
                'position' => 'class_leader',
                'academic_year' => $year,
            ]);

        $response->assertRedirect(route('admin.students.show', $student));

        $this->assertDatabaseHas('credit_transfer_requests', [
            'user_id' => $student->id,
            'position' => 'class_leader',
            'academic_year' => $year,
            'hours_requested' => CreditTransferPosition::hoursMap()['class_leader'],
            'activity_category' => null,
            'status' => 'approved',
            'proof_image_path' => null,
        ]);

        $request = CreditTransferRequest::where('user_id', $student->id)->first();
        $this->assertNotNull($request->reviewed_by);
        $this->assertNotNull($request->reviewed_at);

        Notification::assertSentTo($student, CreditTransferRequestReviewed::class);

        $summary = app(ActivityEvaluationService::class)->summarize($student->fresh());
        $this->assertSame(CreditTransferPosition::hoursMap()['class_leader'], $summary['total_hours']);
        // Counted in the total but not under any of the five categories.
        $this->assertSame(0, array_sum($summary['category_hours']));

        // The admin dashboard's per-category chart must cope with it too.
        $this->actingAs($superAdmin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_super_admin_grants_hours_for_a_position_created_through_the_crud(): void
    {
        $superAdmin = $this->superAdmin();
        $student = $this->student();
        $year = AcademicYearCalculator::forDate(now());

        // Not one of the seeded 7 — proves grant() reads live from the DB
        // rather than assuming a fixed set of positions.
        $this->actingAs($superAdmin)->post(route('admin.credit-transfer-positions.store'), [
            'key' => 'secretary',
            'label' => 'เลขานุการ',
            'hours' => 35,
        ])->assertRedirect();

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.credit-transfers.grant', $student), [
                'position' => 'secretary',
                'academic_year' => $year,
            ]);

        $response->assertRedirect(route('admin.students.show', $student));
        $this->assertDatabaseHas('credit_transfer_requests', [
            'user_id' => $student->id,
            'position' => 'secretary',
            'hours_requested' => 35,
            'status' => 'approved',
        ]);
    }

    public function test_super_admin_can_override_the_hours_when_granting(): void
    {
        $student = $this->student();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.credit-transfers.grant', $student), [
                'position' => 'class_leader',
                'academic_year' => AcademicYearCalculator::forDate(now()),
                'hours_approved' => 30,
            ]);

        $request = CreditTransferRequest::where('user_id', $student->id)->first();
        $this->assertSame(CreditTransferPosition::hoursMap()['class_leader'], $request->hours_requested);
        $this->assertSame(30, $request->hours_approved);
        $this->assertSame(30, $request->hours_credited);
    }

    public function test_hours_approved_is_left_null_when_it_matches_the_positions_standard_hours(): void
    {
        $student = $this->student();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.credit-transfers.grant', $student), [
                'position' => 'class_leader',
                'academic_year' => AcademicYearCalculator::forDate(now()),
                'hours_approved' => CreditTransferPosition::hoursMap()['class_leader'],
            ]);

        $request = CreditTransferRequest::where('user_id', $student->id)->first();
        $this->assertNull($request->hours_approved);
    }

    public function test_a_plain_admin_cannot_grant_credit_transfer_hours(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())
            ->post(route('admin.credit-transfers.grant', $student), [
                'position' => 'class_leader',
                'academic_year' => AcademicYearCalculator::forDate(now()),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('credit_transfer_requests', 0);
    }

    public function test_a_student_cannot_grant_credit_transfer_hours(): void
    {
        $student = $this->student();

        $this->actingAs($this->student())
            ->post(route('admin.credit-transfers.grant', $student), [
                'position' => 'class_leader',
                'academic_year' => AcademicYearCalculator::forDate(now()),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('credit_transfer_requests', 0);
    }

    public function test_granting_a_second_claim_for_the_same_student_and_year_fails_validation(): void
    {
        $student = $this->student();
        $year = AcademicYearCalculator::forDate(now());
        $this->pendingRequest(['user_id' => $student->id, 'academic_year' => $year, 'status' => 'approved']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.credit-transfers.grant', $student), [
                'position' => 'club_president',
                'academic_year' => $year,
            ])
            ->assertSessionHasErrors('academic_year');

        $this->assertDatabaseCount('credit_transfer_requests', 1);
    }

    public function test_grant_requires_a_valid_position(): void
    {
        $student = $this->student();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.credit-transfers.grant', $student), [
                'position' => 'not_a_real_position',
                'academic_year' => AcademicYearCalculator::forDate(now()),
            ])
            ->assertSessionHasErrors('position');

        $this->assertDatabaseCount('credit_transfer_requests', 0);
    }

    // --- revoke() ---

    public function test_super_admin_revokes_an_approved_request_and_the_hours_drop_off(): void
    {
        Notification::fake();
        $student = $this->student();
        $request = $this->pendingRequest([
            'user_id' => $student->id,
            'status' => 'approved',
            'activity_category' => 'volunteer',
            'reviewed_by' => $this->admin()->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->post(route('admin.credit-transfers.revoke', $request), [
                'reject_reason' => 'อนุมัติผิดตำแหน่ง นักศึกษาไม่ได้ดำรงตำแหน่งนี้จริง',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('credit_transfer_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reject_reason' => 'อนุมัติผิดตำแหน่ง นักศึกษาไม่ได้ดำรงตำแหน่งนี้จริง',
        ]);

        $summary = app(ActivityEvaluationService::class)->summarize($student->fresh());
        $this->assertSame(0, $summary['total_hours']);

        Notification::assertSentTo($student, CreditTransferRequestReviewed::class);
    }

    public function test_a_plain_admin_cannot_revoke_an_approved_request(): void
    {
        $request = $this->pendingRequest(['status' => 'approved', 'activity_category' => 'volunteer']);

        $this->actingAs($this->admin())
            ->post(route('admin.credit-transfers.revoke', $request), ['reject_reason' => 'ทดสอบ'])
            ->assertForbidden();

        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_a_student_cannot_revoke_an_approved_request(): void
    {
        $request = $this->pendingRequest(['status' => 'approved', 'activity_category' => 'volunteer']);

        $this->actingAs($this->student())
            ->post(route('admin.credit-transfers.revoke', $request), ['reject_reason' => 'ทดสอบ'])
            ->assertForbidden();

        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_revoke_fails_on_a_request_that_is_not_approved(): void
    {
        $request = $this->pendingRequest(['status' => 'pending']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.credit-transfers.revoke', $request), ['reject_reason' => 'ทดสอบ'])
            ->assertStatus(422);

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_revoke_requires_a_reason(): void
    {
        $request = $this->pendingRequest(['status' => 'approved', 'activity_category' => 'volunteer']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.credit-transfers.revoke', $request), [])
            ->assertSessionHasErrors('reject_reason');

        $this->assertSame('approved', $request->fresh()->status);
    }
}
