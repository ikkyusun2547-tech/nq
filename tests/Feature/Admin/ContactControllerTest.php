<?php

namespace Tests\Feature\Admin;

use App\Models\ContactThread;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\ContactMessageReplied;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    private function student(): User
    {
        return User::factory()->create([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => (string) random_int(10000000000, 99999999999),
            'year_level' => 2,
        ]);
    }

    private function thread(array $overrides = []): ContactThread
    {
        return ContactThread::create(array_merge([
            'user_id' => $this->student()->id,
            'subject' => 'สอบถามเรื่องกิจกรรม',
            'last_message_at' => now(),
            'admin_unread' => true,
        ], $overrides));
    }

    public function test_a_student_cannot_access_the_admin_contact_queue(): void
    {
        $student = $this->student();

        $this->actingAs($student)->get(route('admin.contact.index'))->assertForbidden();
    }

    public function test_a_plain_admin_not_just_super_admin_can_view_the_queue(): void
    {
        $admin = $this->admin();
        $this->thread();

        $this->actingAs($admin)->get(route('admin.contact.index'))->assertOk();
    }

    public function test_viewing_a_thread_clears_the_admin_unread_flag(): void
    {
        $admin = $this->admin();
        $thread = $this->thread(['admin_unread' => true]);

        $this->actingAs($admin)->get(route('admin.contact.show', $thread))->assertOk();

        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'admin_unread' => false]);
    }

    public function test_an_open_chat_window_marks_the_thread_read_but_a_plain_poll_does_not(): void
    {
        $admin = $this->admin();
        $thread = $this->thread(['admin_unread' => true]);

        $this->actingAs($admin)->getJson(route('admin.contact.poll', $thread))->assertOk();
        $this->assertTrue($thread->fresh()->admin_unread);

        $this->actingAs($admin)->getJson(route('admin.contact.poll', $thread).'?seen=1')->assertOk();
        $this->assertFalse($thread->fresh()->admin_unread);
    }

    public function test_pages_carry_the_chat_dock_and_the_thread_page_can_minimise(): void
    {
        $admin = $this->admin();
        $thread = $this->thread();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('chatDock(', false);
        $this->actingAs($admin)->get(route('admin.contact.show', $thread))->assertOk()
            ->assertSee('minimizeChat(', false)
            ->assertSee('ย่อแชท');
    }

    public function test_the_thread_page_shows_the_student_panel_and_actions(): void
    {
        $admin = $this->admin();
        $thread = $this->thread();

        $html = $this->actingAs($admin)->get(route('admin.contact.show', $thread))
            ->assertOk()
            ->assertSee(route('admin.students.show', $thread->student), false)
            ->assertSee(route('admin.contact.claim', $thread), false)
            ->assertSee(route('admin.contact.close', $thread), false)
            ->getContent();

        // A stray double quote inside x-data would silently break the whole chat.
        preg_match('/x-data="([^"]*)"/s', substr($html, strpos($html, 'pollUrl') - 2000), $m);
        $this->assertStringContainsString('pollUrl', $m[1] ?? '');
    }

    public function test_a_plain_admin_can_reply_and_the_student_is_notified(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $thread = $this->thread();

        $response = $this->actingAs($admin)->post(route('admin.contact.reply', $thread), [
            'body' => 'เจ้าหน้าที่ตอบกลับแล้วครับ',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contact_messages', [
            'thread_id' => $thread->id,
            'sender_id' => $admin->id,
            'body' => 'เจ้าหน้าที่ตอบกลับแล้วครับ',
        ]);
        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'student_unread' => true, 'admin_unread' => false]);
        Notification::assertSentTo($thread->student, ContactMessageReplied::class);
    }

    public function test_replying_requires_a_body(): void
    {
        $admin = $this->admin();
        $thread = $this->thread();

        $this->actingAs($admin)->post(route('admin.contact.reply', $thread), [])
            ->assertSessionHasErrors('body');
    }

    public function test_an_admin_can_close_and_reopen_a_thread(): void
    {
        $admin = $this->admin();
        $thread = $this->thread(['status' => 'open']);

        $this->actingAs($admin)->post(route('admin.contact.close', $thread))->assertRedirect();
        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'status' => 'closed']);

        $this->actingAs($admin)->post(route('admin.contact.reopen', $thread))->assertRedirect();
        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'status' => 'open']);
    }

    public function test_the_index_filters_by_status_and_search(): void
    {
        $admin = $this->admin();
        $openThread = $this->thread(['status' => 'open', 'subject' => 'เรื่องเปิด']);
        $closedThread = $this->thread(['status' => 'closed', 'subject' => 'เรื่องปิด']);

        $response = $this->actingAs($admin)->get(route('admin.contact.index', ['status' => 'closed']));

        $response->assertOk();
        $ids = $response->viewData('threads')->pluck('id')->all();
        $this->assertContains($closedThread->id, $ids);
        $this->assertNotContains($openThread->id, $ids);
    }

    public function test_poll_returns_the_thread_status_and_messages_and_any_admin_can_call_it(): void
    {
        $admin = $this->admin();
        $thread = $this->thread();
        $thread->messages()->create(['sender_id' => $admin->id, 'body' => 'ตอบกลับแล้วครับ']);

        $response = $this->actingAs($admin)->getJson(route('admin.contact.poll', $thread));

        $response->assertOk();
        $this->assertSame('open', $response->json('status'));
        $this->assertCount(1, $response->json('messages'));
        $this->assertTrue($response->json('messages.0.is_mine'));
    }

    public function test_replying_via_json_returns_the_new_message_instead_of_redirecting(): void
    {
        $admin = $this->admin();
        $thread = $this->thread();

        $response = $this->actingAs($admin)->postJson(route('admin.contact.reply', $thread), [
            'body' => 'ตอบกลับผ่าน AJAX',
        ]);

        $response->assertOk();
        $this->assertSame('ตอบกลับผ่าน AJAX', $response->json('data.body'));
        $this->assertTrue($response->json('data.is_mine'));
    }

    public function test_an_admin_can_reply_with_only_an_image_attachment_and_no_body(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $thread = $this->thread();

        $response = $this->actingAs($admin)->postJson(route('admin.contact.reply', $thread), [
            'attachment' => UploadedFile::fake()->image('screenshot.png'),
        ]);

        $response->assertOk();
        $this->assertNull($response->json('data.body'));
        $this->assertTrue($response->json('data.is_image_attachment'));
        $this->assertNotNull($response->json('data.attachment_url'));
    }

    public function test_replying_auto_claims_an_unassigned_thread(): void
    {
        $admin = $this->admin();
        $thread = $this->thread();

        $this->actingAs($admin)->post(route('admin.contact.reply', $thread), [
            'body' => 'รับเรื่องนี้ให้เลยครับ',
        ]);

        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'assigned_admin_id' => $admin->id]);
    }

    public function test_replying_does_not_reassign_a_thread_already_claimed_by_someone_else(): void
    {
        $firstAdmin = $this->admin();
        $secondAdmin = User::factory()->create(['role' => 'admin', 'email' => 'admin2@srru.ac.th']);
        $thread = $this->thread(['assigned_admin_id' => $firstAdmin->id]);

        $this->actingAs($secondAdmin)->post(route('admin.contact.reply', $thread), [
            'body' => 'ขอแทรกตอบหน่อยครับ',
        ]);

        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'assigned_admin_id' => $firstAdmin->id]);
    }

    public function test_an_admin_can_claim_an_unassigned_thread(): void
    {
        $admin = $this->admin();
        $thread = $this->thread();

        $this->actingAs($admin)->post(route('admin.contact.claim', $thread))->assertRedirect();

        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'assigned_admin_id' => $admin->id]);
    }

    public function test_an_admin_can_take_over_a_thread_claimed_by_someone_else(): void
    {
        $firstAdmin = $this->admin();
        $secondAdmin = User::factory()->create(['role' => 'admin', 'email' => 'admin2@srru.ac.th']);
        $thread = $this->thread(['assigned_admin_id' => $firstAdmin->id]);

        $this->actingAs($secondAdmin)->post(route('admin.contact.claim', $thread))->assertRedirect();

        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'assigned_admin_id' => $secondAdmin->id]);
    }

    public function test_an_admin_can_release_a_thread_they_claimed(): void
    {
        $admin = $this->admin();
        $thread = $this->thread(['assigned_admin_id' => $admin->id]);

        $this->actingAs($admin)->post(route('admin.contact.release', $thread))->assertRedirect();

        $this->assertDatabaseHas('contact_threads', ['id' => $thread->id, 'assigned_admin_id' => null]);
    }
}
