<?php

namespace Tests\Feature\Student;

use App\Models\Attendance;
use App\Models\ContactMessage;
use App\Models\ContactThread;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\ContactMessageReplied;
use App\Notifications\ContactThreadStarted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
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

    public function test_a_student_can_start_a_new_thread_and_admins_are_notified(): void
    {
        Notification::fake();
        $student = $this->student();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $response = $this->actingAs($student)->post(route('contact.store'), [
            'subject' => 'สอบถามเรื่องกิจกรรม',
            'body' => 'อยากสอบถามเรื่องการเช็คชื่อ',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contact_threads', [
            'user_id' => $student->id,
            'subject' => 'สอบถามเรื่องกิจกรรม',
            'status' => 'open',
            'admin_unread' => true,
        ]);
        $this->assertDatabaseHas('contact_messages', [
            'sender_id' => $student->id,
            'body' => 'อยากสอบถามเรื่องการเช็คชื่อ',
        ]);
        Notification::assertSentTo($admin, ContactThreadStarted::class);
    }

    public function test_creating_a_thread_requires_subject_and_body(): void
    {
        $student = $this->student();

        $this->actingAs($student)->post(route('contact.store'), [])
            ->assertSessionHasErrors(['subject', 'body']);
    }

    public function test_a_student_cannot_view_another_students_thread(): void
    {
        $owner = $this->student();
        $other = $this->student();
        $thread = ContactThread::create([
            'user_id' => $owner->id,
            'subject' => 'เรื่องส่วนตัว',
            'last_message_at' => now(),
        ]);

        $this->actingAs($other)->get(route('contact.show', $thread))->assertForbidden();
    }

    public function test_viewing_a_thread_clears_the_students_unread_flag(): void
    {
        $student = $this->student();
        $thread = ContactThread::create([
            'user_id' => $student->id,
            'subject' => 'เรื่องทดสอบ',
            'last_message_at' => now(),
            'student_unread' => true,
        ]);

        $this->actingAs($student)->get(route('contact.show', $thread))->assertOk();

        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'student_unread' => false]);
    }

    public function test_replying_reopens_a_closed_thread_and_notifies_admins(): void
    {
        Notification::fake();
        $student = $this->student();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $thread = ContactThread::create([
            'user_id' => $student->id,
            'subject' => 'เรื่องที่ปิดแล้ว',
            'status' => 'closed',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($student)->post(route('contact.reply', $thread), [
            'body' => 'ขอสอบถามเพิ่มเติมครับ',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'status' => 'open', 'admin_unread' => true]);
        Notification::assertSentTo($admin, ContactMessageReplied::class);
    }

    public function test_a_student_cannot_reply_to_another_students_thread(): void
    {
        $owner = $this->student();
        $other = $this->student();
        $thread = ContactThread::create([
            'user_id' => $owner->id,
            'subject' => 'เรื่องส่วนตัว',
            'last_message_at' => now(),
        ]);

        $this->actingAs($other)->post(route('contact.reply', $thread), ['body' => 'แอบถาม'])
            ->assertForbidden();
    }

    public function test_poll_returns_the_thread_status_and_messages(): void
    {
        $student = $this->student();
        $thread = ContactThread::create([
            'user_id' => $student->id,
            'subject' => 'เรื่องทดสอบ',
            'last_message_at' => now(),
        ]);
        $thread->messages()->create(['sender_id' => $student->id, 'body' => 'ข้อความแรก']);

        $response = $this->actingAs($student)->getJson(route('contact.poll', $thread));

        $response->assertOk();
        $this->assertSame('open', $response->json('status'));
        $this->assertCount(1, $response->json('messages'));
        $this->assertSame('ข้อความแรก', $response->json('messages.0.body'));
        $this->assertTrue($response->json('messages.0.is_mine'));
    }

    public function test_poll_exposes_the_assigned_admins_name_when_claimed(): void
    {
        $student = $this->student();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th', 'name' => 'เจ้าหน้าที่สมศรี']);
        $thread = ContactThread::create([
            'user_id' => $student->id,
            'assigned_admin_id' => $admin->id,
            'subject' => 'เรื่องทดสอบ',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($student)->getJson(route('contact.poll', $thread));

        $response->assertOk();
        $this->assertSame($admin->name_thai ?? $admin->name, $response->json('assigned_admin_name'));
    }

    public function test_poll_returns_null_assigned_admin_name_when_unclaimed(): void
    {
        $student = $this->student();
        $thread = ContactThread::create([
            'user_id' => $student->id,
            'subject' => 'เรื่องทดสอบ',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($student)->getJson(route('contact.poll', $thread));

        $response->assertOk();
        $this->assertNull($response->json('assigned_admin_name'));
    }

    public function test_a_student_cannot_poll_another_students_thread(): void
    {
        $owner = $this->student();
        $other = $this->student();
        $thread = ContactThread::create([
            'user_id' => $owner->id,
            'subject' => 'เรื่องส่วนตัว',
            'last_message_at' => now(),
        ]);

        $this->actingAs($other)->getJson(route('contact.poll', $thread))->assertForbidden();
    }

    public function test_replying_via_json_returns_the_new_message_instead_of_redirecting(): void
    {
        $student = $this->student();
        $thread = ContactThread::create([
            'user_id' => $student->id,
            'subject' => 'เรื่องทดสอบ',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($student)->postJson(route('contact.reply', $thread), [
            'body' => 'ข้อความผ่าน AJAX',
        ]);

        $response->assertOk();
        $this->assertSame('ข้อความผ่าน AJAX', $response->json('data.body'));
        $this->assertTrue($response->json('data.is_mine'));
    }

    public function test_create_exposes_the_students_flagged_and_rejected_items_as_topics(): void
    {
        $student = $this->student();
        Attendance::factory()->flagged()->create(['user_id' => $student->id]);

        $response = $this->actingAs($student)->get(route('contact.create'));

        $response->assertOk();
        $topics = $response->viewData('topics');
        $this->assertCount(1, $topics);
        $this->assertSame('checkin', $topics->first()->type);
    }

    public function test_a_student_can_start_a_thread_with_only_an_image_attachment_and_no_body(): void
    {
        Storage::fake('public');
        $student = $this->student();

        $response = $this->actingAs($student)->post(route('contact.store'), [
            'subject' => 'แนบรูปอย่างเดียว',
            'attachment' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contact_messages', ['sender_id' => $student->id, 'body' => null]);
        $stored = ContactMessage::where('sender_id', $student->id)->first();
        $this->assertNotNull($stored->attachment_path);
        Storage::disk('public')->assertExists($stored->attachment_path);
        $this->assertSame('photo.jpg', $stored->attachment_name);
        $this->assertTrue($stored->isImageAttachment());
    }

    public function test_a_non_image_attachment_like_a_pdf_is_accepted(): void
    {
        Storage::fake('public');
        $student = $this->student();
        $thread = ContactThread::create([
            'user_id' => $student->id,
            'subject' => 'เรื่องทดสอบ',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($student)->postJson(route('contact.reply', $thread), [
            'attachment' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
        ]);

        $response->assertOk();
        $this->assertFalse($response->json('data.is_image_attachment'));
        $this->assertSame('document.pdf', $response->json('data.attachment_name'));
    }

    public function test_a_disallowed_file_type_is_rejected(): void
    {
        Storage::fake('public');
        $student = $this->student();
        $thread = ContactThread::create([
            'user_id' => $student->id,
            'subject' => 'เรื่องทดสอบ',
            'last_message_at' => now(),
        ]);

        $this->actingAs($student)->postJson(route('contact.reply', $thread), [
            'attachment' => UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload'),
        ])->assertStatus(422)->assertJsonValidationErrors('attachment');
    }

    public function test_replying_with_neither_body_nor_attachment_is_rejected(): void
    {
        $student = $this->student();
        $thread = ContactThread::create([
            'user_id' => $student->id,
            'subject' => 'เรื่องทดสอบ',
            'last_message_at' => now(),
        ]);

        $this->actingAs($student)->postJson(route('contact.reply', $thread), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body', 'attachment']);
    }
}
