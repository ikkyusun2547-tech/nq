<?php

namespace Tests\Feature\Admin;

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
}
