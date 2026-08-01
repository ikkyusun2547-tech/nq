<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_cannot_view_the_audit_log(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.audit-log.index'))->assertForbidden();
    }

    public function test_it_lists_reviewed_attendances(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $reviewer = User::factory()->create(['role' => 'admin', 'email' => 'reviewer@srru.ac.th', 'name_thai' => 'ผู้ตรวจสอบ']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th', 'name_thai' => 'นักศึกษาทดสอบ']);
        $activity = Activity::factory()->create(['title' => 'กิจกรรมทดสอบ']);

        Attendance::factory()->for($student)->for($activity)->create([
            'status' => 'rejected',
            'reject_reason' => 'ทดสอบ',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        // Never reviewed — must not show up.
        Attendance::factory()->for($student)->for(Activity::factory())->create(['status' => 'flagged', 'reviewed_by' => null]);

        $response = $this->actingAs($admin)->get(route('admin.audit-log.index'));

        $response->assertOk();
        $response->assertSee('นักศึกษาทดสอบ');
        $response->assertSee('กิจกรรมทดสอบ');
        $response->assertSee('ผู้ตรวจสอบ');
    }

    public function test_it_lists_admin_actions_alongside_reviewed_records(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'email' => 'super@srru.ac.th', 'name_thai' => 'ผู้ดูแลระบบ']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th', 'name_thai' => 'จะถูกเลื่อนสิทธิ์']);

        $this->actingAs($superAdmin)->post(route('admin.users.promote', $student));

        $response = $this->actingAs($superAdmin)->get(route('admin.audit-log.index'));

        $response->assertOk();
        $response->assertSee('ผู้ดูแลระบบ');
        $response->assertSee('จะถูกเลื่อนสิทธิ์');
        $response->assertSee('เลื่อนสิทธิ์');
    }

    public function test_it_filters_by_reviewer(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $reviewerA = User::factory()->create(['role' => 'admin', 'email' => 'a@srru.ac.th', 'name_thai' => 'ผู้ตรวจ A']);
        $reviewerB = User::factory()->create(['role' => 'admin', 'email' => 'b@srru.ac.th', 'name_thai' => 'ผู้ตรวจ B']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        Attendance::factory()->for($student)->for(Activity::factory()->create(['title' => 'งาน A']))->create([
            'status' => 'auto_approved', 'reviewed_by' => $reviewerA->id, 'reviewed_at' => now(),
        ]);
        Attendance::factory()->for($student)->for(Activity::factory()->create(['title' => 'งาน B']))->create([
            'status' => 'auto_approved', 'reviewed_by' => $reviewerB->id, 'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.audit-log.index', ['reviewer_id' => $reviewerA->id]));

        $response->assertOk();
        $response->assertSee('งาน A');
        $response->assertDontSee('งาน B');
    }

    public function test_it_sorts_by_reviewer_name_ascending_against_the_union_subquery_alias(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $reviewerZ = User::factory()->create(['role' => 'admin', 'email' => 'z@srru.ac.th', 'name_thai' => 'ฮ ผู้ตรวจท้ายสุด']);
        $reviewerA = User::factory()->create(['role' => 'admin', 'email' => 'a@srru.ac.th', 'name_thai' => 'ก ผู้ตรวจแรกสุด']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        Attendance::factory()->for($student)->for(Activity::factory())->create([
            'status' => 'auto_approved', 'reviewed_by' => $reviewerZ->id, 'reviewed_at' => now(),
        ]);
        Attendance::factory()->for($student)->for(Activity::factory())->create([
            'status' => 'auto_approved', 'reviewed_by' => $reviewerA->id, 'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.audit-log.index', ['sort' => 'reviewer_name', 'dir' => 'asc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, 'ก ผู้ตรวจแรกสุด') < strpos($content, 'ฮ ผู้ตรวจท้ายสุด'));
    }

    public function test_it_sorts_by_title_ascending(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $reviewer = User::factory()->create(['role' => 'admin', 'email' => 'r@srru.ac.th']);
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        Attendance::factory()->for($student)->for(Activity::factory()->create(['title' => 'Zebra Event']))->create([
            'status' => 'auto_approved', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(),
        ]);
        Attendance::factory()->for($student)->for(Activity::factory()->create(['title' => 'Alpha Event']))->create([
            'status' => 'auto_approved', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.audit-log.index', ['sort' => 'title', 'dir' => 'asc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, 'Alpha Event') < strpos($content, 'Zebra Event'));
    }
}
