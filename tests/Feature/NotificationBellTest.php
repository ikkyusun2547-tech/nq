<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create([
            'role' => 'student', 'email' => 'stu'.uniqid().'@srru.ac.th', 'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901', 'year_level' => 2, 'program_type' => 'normal',
        ]);
    }

    private function notify(User $user, \DateTimeInterface $at, bool $read = false): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\Announcement',
            'data' => ['icon' => 'chat', 'title_key' => 'ประกาศ', 'body_key' => 'ทดสอบ', 'url' => '/'],
            'read_at' => $read ? now() : null,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    public function test_the_poll_feed_groups_items_by_day(): void
    {
        app()->setLocale('th');
        $student = $this->student();
        $this->notify($student, now()->subDays(5));
        $this->notify($student, now()->subDay()->setTime(12, 0));
        $this->notify($student, now()->subMinutes(5));

        $response = $this->actingAs($student)->getJson(route('notifications.poll'))->assertOk();

        $this->assertSame(3, $response->json('unread_count'));
        $this->assertSame(['วันนี้', 'เมื่อวาน', 'ก่อนหน้านี้'], array_column($response->json('notifications'), 'group'));
    }

    public function test_read_all_answers_json_for_the_bell_and_redirects_for_forms(): void
    {
        $student = $this->student();
        $this->notify($student, now());

        $this->actingAs($student)->postJson(route('notifications.read-all'))->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(0, $student->unreadNotifications()->count());

        $this->notify($student, now());
        $this->actingAs($student)->from(route('dashboard'))->post(route('notifications.read-all'))->assertRedirect(route('dashboard'));
    }

    public function test_the_bell_partial_is_well_formed(): void
    {
        // A half-replaced panel once left a second copy of its markup behind,
        // which pushed the header's controls out of the bar.
        $source = file_get_contents(resource_path('views/partials/notification-bell.blade.php'));
        $this->assertSame(substr_count($source, '<template'), substr_count($source, '</template>'));
        $this->assertSame(preg_match_all('/<div[\s>]/', $source), substr_count($source, '</div>'));

        $html = $this->actingAs($this->student())->get(route('dashboard'))->getContent();
        $this->assertSame(1, substr_count($html, 'ดูการแจ้งเตือนทั้งหมด'));
    }

    public function test_the_bell_panel_renders_its_tabs_and_empty_state(): void
    {
        $this->actingAs($this->student())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ยังไม่อ่าน')
            ->assertSee('กิจกรรมใหม่ ผลคำร้อง และข้อความจากเจ้าหน้าที่ จะขึ้นที่นี่')
            ->assertSee('ดูการแจ้งเตือนทั้งหมด');
    }
}
