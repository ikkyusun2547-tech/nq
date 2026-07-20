<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use App\Notifications\AttendanceApproved;
use App\Notifications\AttendanceRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);
    }

    // --- authorization ---

    public function test_a_student_cannot_view_the_attendance_matrix(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs($this->student())
            ->get(route('admin.attendance.index', $activity))
            ->assertForbidden();
    }

    public function test_a_student_cannot_approve_a_flagged_attendance(): void
    {
        $attendance = Attendance::factory()->flagged()->create();

        $this->actingAs($this->student())
            ->post(route('admin.attendance.approve', $attendance))
            ->assertForbidden();
    }

    public function test_a_student_cannot_view_the_flagged_queue(): void
    {
        $this->actingAs($this->student())
            ->get(route('admin.attendance.flagged'))
            ->assertForbidden();
    }

    // --- index() ---

    public function test_the_attendance_matrix_loads_with_counts(): void
    {
        $activity = Activity::factory()->create();
        Attendance::factory()->for($activity)->create();
        Attendance::factory()->for($activity)->flagged()->create();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.index', $activity));

        $response->assertOk();
        $response->assertViewHas('checkedInCount', 2);
    }

    public function test_the_attendance_matrix_filters_by_status(): void
    {
        $activity = Activity::factory()->create();
        $approved = Attendance::factory()->for($activity)->create();
        $flagged = Attendance::factory()->for($activity)->flagged()->create();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.index', ['activity' => $activity, 'status' => 'flagged']));

        $response->assertOk();
        $ids = $response->viewData('attendances')->pluck('id');
        $this->assertTrue($ids->contains($flagged->id));
        $this->assertFalse($ids->contains($approved->id));
    }

    // --- bulkApprove() ---

    public function test_bulk_approve_stamps_flagged_rows_and_notifies_only_newly_approved_ones(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $activity = Activity::factory()->create();
        $flagged = Attendance::factory()->for($activity)->flagged()->create();
        $alreadyApproved = Attendance::factory()->for($activity)->create();

        $response = $this->actingAs($admin)->post(route('admin.attendance.bulk-approve', $activity), [
            'attendance_ids' => [$flagged->id, $alreadyApproved->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'id' => $flagged->id,
            'status' => 'auto_approved',
            'reviewed_by' => $admin->id,
        ]);

        Notification::assertSentTo($flagged->user, AttendanceApproved::class);
        Notification::assertSentTimes(AttendanceApproved::class, 1);
    }

    public function test_bulk_approve_requires_at_least_one_id(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.attendance.bulk-approve', $activity), ['attendance_ids' => []])
            ->assertSessionHasErrors('attendance_ids');
    }

    // --- flaggedIndex() ---

    public function test_flagged_queue_defaults_to_flagged_status(): void
    {
        $flagged = Attendance::factory()->flagged()->create();
        $rejected = Attendance::factory()->create(['status' => 'rejected']);
        $approved = Attendance::factory()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.attendance.flagged'));

        $response->assertOk();
        $ids = $response->viewData('attendances')->pluck('id');
        $this->assertTrue($ids->contains($flagged->id));
        $this->assertFalse($ids->contains($rejected->id));
        $this->assertFalse($ids->contains($approved->id));
    }

    public function test_flagged_queue_tab_counts_are_independent_of_the_search_filter(): void
    {
        Attendance::factory()->count(2)->flagged()->create();
        Attendance::factory()->count(3)->create(['status' => 'rejected']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.flagged', ['search' => 'no-such-student']));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('tabCounts')['flagged']);
        $this->assertSame(3, $response->viewData('tabCounts')['rejected']);
        $this->assertSame(5, $response->viewData('tabCounts')['all']);
    }

    // --- approve() ---

    public function test_approve_marks_a_flagged_attendance_as_auto_approved(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $attendance = Attendance::factory()->flagged()->create();

        $response = $this->actingAs($admin)->post(route('admin.attendance.approve', $attendance));

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'auto_approved',
            'reviewed_by' => $admin->id,
        ]);
        Notification::assertSentTo($attendance->user, AttendanceApproved::class);
    }

    public function test_approving_an_already_resolved_attendance_is_a_no_op(): void
    {
        Notification::fake();
        $attendance = Attendance::factory()->create(); // already auto_approved

        $response = $this->actingAs($this->admin())->post(route('admin.attendance.approve', $attendance));

        $response->assertRedirect()->assertSessionHas('error');
        Notification::assertNothingSent();
    }

    // --- reject() ---

    public function test_reject_requires_a_reason(): void
    {
        $attendance = Attendance::factory()->flagged()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.attendance.reject', $attendance), [])
            ->assertSessionHasErrors('reject_reason');
    }

    public function test_reject_marks_a_flagged_attendance_as_rejected(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $attendance = Attendance::factory()->flagged()->create();

        $response = $this->actingAs($admin)->post(route('admin.attendance.reject', $attendance), [
            'reject_reason' => 'ภาพเซลฟีไม่ตรงกับตัวตนในระบบ',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'rejected',
            'reject_reason' => 'ภาพเซลฟีไม่ตรงกับตัวตนในระบบ',
            'reviewed_by' => $admin->id,
        ]);
        Notification::assertSentTo($attendance->user, AttendanceRejected::class);
    }

    public function test_rejecting_an_already_resolved_attendance_is_a_no_op(): void
    {
        $attendance = Attendance::factory()->create(['status' => 'rejected']);

        $response = $this->actingAs($this->admin())->post(route('admin.attendance.reject', $attendance), [
            'reject_reason' => 'เหตุผลใหม่',
        ]);

        $response->assertRedirect()->assertSessionHas('error');
    }

    // --- exports ---

    public function test_it_downloads_the_attendees_excel_export(): void
    {
        $activity = Activity::factory()->create();
        Attendance::factory()->for($activity)->create();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.export', $activity));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_it_downloads_the_missing_students_excel_export(): void
    {
        $activity = Activity::factory()->create();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.missing-export', $activity));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    // --- QR endpoints ---

    public function test_qr_display_loads_for_an_open_activity(): void
    {
        $activity = Activity::factory()->create(['status' => 'open']);

        $this->actingAs($this->admin())
            ->get(route('admin.attendance.qr-display', $activity))
            ->assertOk();
    }

    public function test_qr_fragment_returns_the_closed_view_when_the_activity_no_longer_accepts_checkins(): void
    {
        $activity = Activity::factory()->closed()->create();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.qr-fragment', $activity));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.qr-fragment-closed');
    }

    public function test_qr_fragment_mints_a_live_token_for_an_open_activity(): void
    {
        $activity = Activity::factory()->create(['status' => 'open']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.qr-fragment', $activity));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.qr-fragment');
    }
}
