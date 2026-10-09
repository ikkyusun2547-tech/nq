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

    public function test_flagged_rows_get_inline_approve_and_reject_while_approved_rows_do_not(): void
    {
        $activity = Activity::factory()->create();
        $ok = Attendance::factory()->for($activity)->create();
        $flagged = Attendance::factory()->for($activity)->flagged()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.attendance.index', $activity))->assertOk();

        $this->assertSame(1, (int) $response->viewData('statusCounts')['flagged']);
        $response->assertSee(route('admin.attendance.approve', $flagged), false)
            ->assertSee(route('admin.attendance.reject', $flagged), false)
            ->assertDontSee(route('admin.attendance.approve', $ok), false)
            ->assertDontSee('อนุมัติที่ถูกต้อง');
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

    public function test_the_attendance_matrix_sorts_by_year_level_via_a_join_on_users(): void
    {
        $activity = Activity::factory()->create();
        $senior = User::factory()->create(['role' => 'student', 'email' => 'senior@srru.ac.th', 'year_level' => 4]);
        $junior = User::factory()->create(['role' => 'student', 'email' => 'junior@srru.ac.th', 'year_level' => 1]);
        Attendance::factory()->for($activity)->for($senior)->create();
        Attendance::factory()->for($activity)->for($junior)->create();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.index', ['activity' => $activity, 'sort' => 'year_level', 'dir' => 'asc']));

        $response->assertOk();
        $years = $response->viewData('attendances')->pluck('user.year_level')->all();

        $this->assertSame([1, 4], $years);
    }

    public function test_the_attendance_matrix_sorts_by_student_id_via_the_same_join(): void
    {
        $activity = Activity::factory()->create();
        $b = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th', 'student_id' => '20000000002']);
        $a = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th', 'student_id' => '10000000001']);
        Attendance::factory()->for($activity)->for($b)->create();
        Attendance::factory()->for($activity)->for($a)->create();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.index', ['activity' => $activity, 'sort' => 'student_id', 'dir' => 'asc']));

        $response->assertOk();
        $ids = $response->viewData('attendances')->pluck('user.student_id')->all();

        $this->assertSame(['10000000001', '20000000002'], $ids);
    }

    public function test_the_attendance_matrix_sorts_by_distance_meters(): void
    {
        $activity = Activity::factory()->create();
        Attendance::factory()->for($activity)->create(['distance_meters' => 500]);
        Attendance::factory()->for($activity)->create(['distance_meters' => 10]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.index', ['activity' => $activity, 'sort' => 'distance_meters', 'dir' => 'asc']));

        $response->assertOk();
        $distances = $response->viewData('attendances')->pluck('distance_meters')->all();

        $this->assertSame([10, 500], $distances);
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

    public function test_the_flagged_queue_sorts_by_activity_title_via_a_join(): void
    {
        $zebra = Activity::factory()->create(['title' => 'Zebra Activity']);
        $alpha = Activity::factory()->create(['title' => 'Alpha Activity']);
        Attendance::factory()->for($zebra)->flagged()->create();
        Attendance::factory()->for($alpha)->flagged()->create();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.attendance.flagged', ['sort' => 'activity', 'dir' => 'asc']));

        $response->assertOk();
        $titles = $response->viewData('attendances')->pluck('activity.title')->all();

        $this->assertSame(['Alpha Activity', 'Zebra Activity'], $titles);
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

    public function test_self_report_activities_have_no_qr(): void
    {
        $activity = Activity::factory()->selfReport()->create(['status' => 'open']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.attendance.qr-display', $activity))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.attendance.qr-fragment', $activity))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.attendance.qr-print', $activity))->assertNotFound();

        $this->actingAs($admin)->get(route('admin.attendance.index', $activity))
            ->assertOk()
            ->assertDontSee(route('admin.attendance.qr-display', $activity));
        $this->actingAs($admin)->get(route('admin.activities.index'))
            ->assertDontSee(route('admin.attendance.qr-display', $activity));
    }
}
