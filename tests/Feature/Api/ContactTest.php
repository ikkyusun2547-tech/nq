<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\ContactThread;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\ContactMessageReplied;
use App\Notifications\ContactThreadStarted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private function studentUser(): User
    {
        return User::factory()->create([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => (string) random_int(10000000000, 99999999999),
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
    }

    public function test_it_creates_a_thread_and_notifies_admins(): void
    {
        Notification::fake();
        $user = $this->studentUser();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/contact', [
            'subject' => 'สอบถามเรื่องกิจกรรม',
            'body' => 'อยากสอบถามเรื่องการเช็คชื่อ',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('contact_threads', [
            'user_id' => $user->id,
            'subject' => 'สอบถามเรื่องกิจกรรม',
            'status' => 'open',
        ]);
        Notification::assertSentTo($admin, ContactThreadStarted::class);
    }

    public function test_creating_a_thread_requires_subject_and_body(): void
    {
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/contact', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'body']);
    }

    public function test_it_lists_only_the_authenticated_students_threads(): void
    {
        $user = $this->studentUser();
        $other = $this->studentUser();
        Sanctum::actingAs($user);

        ContactThread::create(['user_id' => $user->id, 'subject' => 'ของฉัน', 'last_message_at' => now()]);
        ContactThread::create(['user_id' => $other->id, 'subject' => 'ของคนอื่น', 'last_message_at' => now()]);

        $response = $this->getJson('/api/contact');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('ของฉัน', $response->json('data.0.subject'));
    }

    public function test_a_student_cannot_view_another_students_thread(): void
    {
        $owner = $this->studentUser();
        $other = $this->studentUser();
        Sanctum::actingAs($other);

        $thread = ContactThread::create(['user_id' => $owner->id, 'subject' => 'เรื่องส่วนตัว', 'last_message_at' => now()]);

        $this->getJson("/api/contact/{$thread->id}")->assertForbidden();
    }

    public function test_replying_reopens_a_closed_thread_and_notifies_admins(): void
    {
        Notification::fake();
        $user = $this->studentUser();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        Sanctum::actingAs($user);

        $thread = ContactThread::create([
            'user_id' => $user->id,
            'subject' => 'เรื่องที่ปิดแล้ว',
            'status' => 'closed',
            'last_message_at' => now(),
        ]);

        $response = $this->postJson("/api/contact/{$thread->id}/messages", [
            'body' => 'ขอสอบถามเพิ่มเติมครับ',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'status' => 'open']);
        Notification::assertSentTo($admin, ContactMessageReplied::class);
    }

    public function test_topics_lists_the_students_flagged_and_rejected_items(): void
    {
        $user = $this->studentUser();
        Sanctum::actingAs($user);
        Attendance::factory()->flagged()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/contact/topics');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('checkin', $response->json('data.0.type'));
        $this->assertNotNull($response->json('data.0.reason'));
    }

    public function test_it_creates_a_thread_with_only_an_image_attachment_and_no_body(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $response = $this->post('/api/contact', [
            'subject' => 'แนบรูปอย่างเดียว',
            'attachment' => UploadedFile::fake()->image('photo.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertDatabaseHas('contact_messages', ['sender_id' => $user->id, 'body' => null]);
    }

    public function test_it_exposes_the_assigned_admins_name_on_the_thread_list_and_detail(): void
    {
        $user = $this->studentUser();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th', 'name' => 'เจ้าหน้าที่สมศรี']);
        Sanctum::actingAs($user);

        $thread = ContactThread::create([
            'user_id' => $user->id,
            'assigned_admin_id' => $admin->id,
            'subject' => 'เรื่องทดสอบ',
            'last_message_at' => now(),
        ]);

        $listResponse = $this->getJson('/api/contact');
        $listResponse->assertOk();
        $this->assertSame($admin->name_thai ?? $admin->name, $listResponse->json('data.0.assigned_admin_name'));

        $showResponse = $this->getJson("/api/contact/{$thread->id}");
        $showResponse->assertOk();
        $this->assertSame($admin->name_thai ?? $admin->name, $showResponse->json('thread.assigned_admin_name'));
    }

    public function test_it_returns_null_assigned_admin_name_when_unclaimed(): void
    {
        $user = $this->studentUser();
        Sanctum::actingAs($user);
        ContactThread::create(['user_id' => $user->id, 'subject' => 'เรื่องทดสอบ', 'last_message_at' => now()]);

        $response = $this->getJson('/api/contact');

        $response->assertOk();
        $this->assertNull($response->json('data.0.assigned_admin_name'));
    }

    public function test_office_info_returns_the_configured_contact_details(): void
    {
        config([
            'services.srru.office_phone' => '044-000000',
            'services.srru.office_email' => 'contact@srru.ac.th',
            'services.srru.office_address' => 'ที่อยู่ทดสอบ',
            'services.srru.office_hours' => 'จันทร์–ศุกร์ 08:30–16:30 น.',
            'services.srru.response_time' => 'ภายใน 1–2 วันทำการ',
        ]);
        $user = $this->studentUser();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/contact/info');

        $response->assertOk();
        $this->assertSame('044-000000', $response->json('data.phone'));
        $this->assertSame('contact@srru.ac.th', $response->json('data.email'));
        $this->assertSame('ที่อยู่ทดสอบ', $response->json('data.address'));
        $this->assertSame('จันทร์–ศุกร์ 08:30–16:30 น.', $response->json('data.hours'));
        $this->assertSame('ภายใน 1–2 วันทำการ', $response->json('data.response_time'));
    }

    public function test_replying_with_a_file_attachment_returns_its_url_and_name(): void
    {
        Storage::fake('public');
        $user = $this->studentUser();
        Sanctum::actingAs($user);
        $thread = ContactThread::create(['user_id' => $user->id, 'subject' => 'เรื่องทดสอบ', 'last_message_at' => now()]);

        $response = $this->post("/api/contact/{$thread->id}/messages", [
            'attachment' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertFalse($response->json('data.is_image_attachment'));
        $this->assertSame('document.pdf', $response->json('data.attachment_name'));
        $this->assertNotNull($response->json('data.attachment_url'));
    }
}
