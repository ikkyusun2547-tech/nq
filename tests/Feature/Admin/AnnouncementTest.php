<?php

namespace Tests\Feature\Admin;

use App\Models\AnnouncementLog;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_cannot_send_announcements(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.announcements.create'))->assertForbidden();
    }

    public function test_a_student_cannot_view_announcement_history(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.announcements.index'))->assertForbidden();
    }

    public function test_it_notifies_every_active_student(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $studentA = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        $studentB = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th']);
        $banned = User::factory()->create(['role' => 'student', 'email' => 'c@srru.ac.th', 'account_status' => 'banned']);

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'subject' => 'ประกาศทดสอบ',
            'body' => 'เนื้อหาประกาศทดสอบ',
        ])->assertRedirect(route('admin.announcements.create'));

        Notification::assertSentTo([$studentA, $studentB], Announcement::class);
        Notification::assertNotSentTo($banned, Announcement::class);
        Notification::assertNotSentTo($admin, Announcement::class);
    }

    public function test_it_filters_recipients_by_faculty(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $targetFaculty = Faculty::factory()->create();
        $otherFaculty = Faculty::factory()->create();
        $inFaculty = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th', 'faculty_id' => $targetFaculty->id]);
        $outsideFaculty = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th', 'faculty_id' => $otherFaculty->id]);

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'subject' => 'ประกาศเฉพาะคณะ',
            'body' => 'เนื้อหา',
            'faculty_id' => $targetFaculty->id,
        ]);

        Notification::assertSentTo($inFaculty, Announcement::class);
        Notification::assertNotSentTo($outsideFaculty, Announcement::class);
    }

    public function test_it_reports_an_error_when_no_student_matches_the_filter(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $faculty = Faculty::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'subject' => 'ไม่มีผู้รับ',
            'body' => 'เนื้อหา',
            'faculty_id' => $faculty->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_sending_an_announcement_logs_it_to_history(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'subject' => 'ประกาศทดสอบ',
            'body' => 'เนื้อหาประกาศทดสอบ',
        ]);

        $this->assertDatabaseHas('announcement_logs', [
            'subject' => 'ประกาศทดสอบ',
            'recipient_count' => 1,
            'sent_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('admin.announcements.index'))
            ->assertOk()
            ->assertSee('ประกาศทดสอบ');
    }

    public function test_it_does_not_log_when_no_student_matches_the_filter(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $faculty = Faculty::factory()->create();

        $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'subject' => 'ไม่มีผู้รับ',
            'body' => 'เนื้อหา',
            'faculty_id' => $faculty->id,
        ]);

        $this->assertSame(0, AnnouncementLog::count());
    }

    public function test_it_sorts_by_recipient_count_descending(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        // Deliberately not "น้อย"/"มาก" (least/most) — the sort-arrow header's
        // own tooltip text ("เรียงน้อยไปมาก") contains "น้อย", so a subject
        // using that word would false-positive match against page chrome
        // rendered before the table rows, not the actual data row.
        AnnouncementLog::create(['subject' => 'ประกาศทดสอบเอ', 'body' => 'b', 'recipient_count' => 3, 'sent_by' => $admin->id]);
        AnnouncementLog::create(['subject' => 'ประกาศทดสอบบี', 'body' => 'b', 'recipient_count' => 30, 'sent_by' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.announcements.index', ['sort' => 'recipient_count', 'dir' => 'desc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, 'ประกาศทดสอบบี') < strpos($content, 'ประกาศทดสอบเอ'));
    }

    public function test_it_sorts_by_sender_name_via_a_join_on_users(): void
    {
        $viewer = User::factory()->create(['role' => 'admin', 'email' => 'viewer@srru.ac.th']);
        $zebra = User::factory()->create(['role' => 'admin', 'email' => 'zebra@srru.ac.th', 'name_thai' => 'ฮ ผู้ส่งท้ายสุด']);
        $alpha = User::factory()->create(['role' => 'admin', 'email' => 'alpha@srru.ac.th', 'name_thai' => 'ก ผู้ส่งแรกสุด']);

        AnnouncementLog::create(['subject' => 'ประกาศ A', 'body' => 'b', 'recipient_count' => 1, 'sent_by' => $zebra->id]);
        AnnouncementLog::create(['subject' => 'ประกาศ B', 'body' => 'b', 'recipient_count' => 1, 'sent_by' => $alpha->id]);

        $response = $this->actingAs($viewer)
            ->get(route('admin.announcements.index', ['sort' => 'sender', 'dir' => 'asc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, 'ก ผู้ส่งแรกสุด') < strpos($content, 'ฮ ผู้ส่งท้ายสุด'));
    }
}
