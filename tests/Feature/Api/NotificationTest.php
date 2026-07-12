<?php

namespace Tests\Feature\Api;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function studentUser(string $email = 'student@srru.ac.th'): User
    {
        return User::factory()->create([
            'role' => 'student',
            'email' => $email,
            'faculty_id' => Faculty::factory(),
            'student_id' => (string) random_int(10000000000, 99999999999),
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
    }

    private function seedNotification(User $user, bool $read = false): string
    {
        $id = (string) Str::uuid();

        $user->notifications()->create([
            'id' => $id,
            'type' => 'App\\Notifications\\AttendanceApproved',
            'data' => [
                'icon' => 'check',
                'title_key' => 'การเช็คชื่อของคุณได้รับการอนุมัติแล้ว',
                'body_key' => 'เจ้าหน้าที่ตรวจสอบและอนุมัติการเช็คชื่อกิจกรรม ":title" ให้คุณแล้ว',
                'body_params' => ['title' => 'ทดสอบกิจกรรม'],
                'url' => '/dashboard',
            ],
            'read_at' => $read ? now() : null,
        ]);

        return $id;
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }

    public function test_it_lists_notifications_with_unread_count(): void
    {
        $user = $this->studentUser();
        $this->seedNotification($user, read: false);
        $this->seedNotification($user, read: true);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('unread_count', 1);
        $response->assertJsonPath('data.0.title', 'การเช็คชื่อของคุณได้รับการอนุมัติแล้ว');
        $response->assertJsonPath('data.0.body', 'เจ้าหน้าที่ตรวจสอบและอนุมัติการเช็คชื่อกิจกรรม "ทดสอบกิจกรรม" ให้คุณแล้ว');
    }

    public function test_it_does_not_leak_another_users_notifications(): void
    {
        $owner = $this->studentUser('owner@srru.ac.th');
        $stranger = $this->studentUser('stranger@srru.ac.th');
        $this->seedNotification($owner);
        Sanctum::actingAs($stranger);

        $response = $this->getJson('/api/notifications');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('unread_count', 0);
    }

    public function test_it_marks_a_single_notification_as_read(): void
    {
        $user = $this->studentUser();
        $id = $this->seedNotification($user, read: false);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/notifications/{$id}/read");

        $response->assertOk();
        $this->assertNotNull($user->notifications()->find($id)->read_at);
    }

    public function test_it_does_not_let_a_user_mark_another_users_notification_as_read(): void
    {
        $owner = $this->studentUser('owner@srru.ac.th');
        $stranger = $this->studentUser('stranger@srru.ac.th');
        $id = $this->seedNotification($owner, read: false);
        Sanctum::actingAs($stranger);

        $this->postJson("/api/notifications/{$id}/read")->assertOk();

        $this->assertNull($owner->notifications()->find($id)->read_at);
    }

    public function test_it_marks_all_notifications_as_read(): void
    {
        $user = $this->studentUser();
        $this->seedNotification($user, read: false);
        $this->seedNotification($user, read: false);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/notifications/read-all');

        $response->assertOk();
        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
