<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\AccountUpdated;
use App\Notifications\ActivityCancelled;
use App\Notifications\ActivityCreated;
use App\Notifications\ActivityEndedSummary;
use App\Notifications\AdminDailyDigest;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\StudentCleared;
use App\Services\ActivityAlerts;
use App\Services\ActivityEvaluationService;
use App\Services\ClearanceWatcher;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Notifications round 2: "passed the criteria", account changes, the
 * organiser's end-of-activity summary, the admin morning digest and the
 * per-user notification settings that filter push / email.
 */
class NotificationsRoundTwoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTime(10, 0));
        $this->admin = User::factory()->create(['role' => 'super_admin', 'email' => 'admin@srru.ac.th', 'account_status' => 'active']);
    }

    private function student(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student', 'email' => 'stu'.uniqid().'@srru.ac.th', 'faculty_id' => Faculty::factory(),
            'student_id' => (string) random_int(10000000000, 99999999999), 'year_level' => 2, 'program_type' => 'normal',
            'account_status' => 'active',
        ], $attributes));
    }

    private function fakeCleared(bool $cleared): void
    {
        $this->mock(ActivityEvaluationService::class, fn ($mock) => $mock->shouldReceive('summarize')
            ->andReturn(['is_cleared' => $cleared, 'total_hours' => 40, 'total_activities' => 12]));
    }

    // --- Passed the criteria ------------------------------------------------

    public function test_a_student_is_congratulated_once_when_they_meet_the_criteria(): void
    {
        Notification::fake();
        $this->fakeCleared(true);
        $student = $this->student();

        app(ClearanceWatcher::class)->check($student);
        app(ClearanceWatcher::class)->check($student->fresh());

        Notification::assertSentToTimes($student, StudentCleared::class, 1);
        $this->assertNotNull($student->fresh()->cleared_notified_at);
    }

    public function test_no_congratulation_before_the_criteria_are_met_or_for_admins(): void
    {
        Notification::fake();
        $this->fakeCleared(false);
        $student = $this->student();

        app(ClearanceWatcher::class)->check($student);
        app(ClearanceWatcher::class)->check($this->admin);
        app(ClearanceWatcher::class)->check(null);

        Notification::assertNothingSent();
        $this->assertNull($student->fresh()->cleared_notified_at);
    }

    public function test_an_approved_check_in_triggers_the_clearance_check(): void
    {
        Notification::fake();
        $this->fakeCleared(true);
        $student = $this->student();

        Attendance::factory()->for(Activity::factory()->create(['created_by' => $this->admin->id]))
            ->create(['user_id' => $student->id, 'status' => 'auto_approved']);

        Notification::assertSentToTimes($student, StudentCleared::class, 1);
    }

    // --- Account changes ----------------------------------------------------

    public function test_ban_and_unban_tell_the_user(): void
    {
        Notification::fake();
        $student = $this->student();

        $this->actingAs($this->admin)->post(route('admin.users.ban', $student))->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.users.unban', $student))->assertRedirect();

        Notification::assertSentTo($student, AccountUpdated::class, fn ($n) => $n->toDatabase($student)['title_key'] === 'บัญชีของคุณถูกระงับการใช้งาน');
        Notification::assertSentTo($student, AccountUpdated::class, fn ($n) => $n->toDatabase($student)['title_key'] === 'บัญชีของคุณใช้งานได้อีกครั้ง');
    }

    public function test_promote_tells_the_user(): void
    {
        Notification::fake();
        $student = $this->student();

        $this->actingAs($this->admin)->post(route('admin.users.promote', $student))->assertRedirect();

        Notification::assertSentTo($student, AccountUpdated::class, fn ($n) => $n->toDatabase($student)['title_key'] === 'บัญชีของคุณได้รับสิทธิ์แอดมิน');
    }

    // --- End-of-activity summary --------------------------------------------

    public function test_organisers_get_one_summary_when_an_activity_ends(): void
    {
        Notification::fake();
        $activity = Activity::factory()->create([
            'created_by' => $this->admin->id, 'status' => 'open',
            'start_at' => now()->subHours(3), 'end_at' => now()->subHour(),
        ]);
        Attendance::factory()->for($activity)->create(['user_id' => $this->student()->id, 'status' => 'flagged']);

        $this->artisan('app:close-ended-activities')->assertSuccessful();
        app(ActivityAlerts::class)->ended($activity);

        Notification::assertSentToTimes($this->admin, ActivityEndedSummary::class, 1);
        Notification::assertSentTo($this->admin, ActivityEndedSummary::class, function ($n) {
            $data = $n->toDatabase($this->admin);

            return $data['body_params']['attended'] === 1 && $data['body_params']['review'] === 1;
        });
    }

    // --- Admin morning digest -----------------------------------------------

    public function test_the_digest_counts_waiting_and_stale_items(): void
    {
        Notification::fake();
        $activity = Activity::factory()->create(['created_by' => $this->admin->id]);
        Attendance::factory()->for($activity)->create(['user_id' => $this->student()->id, 'status' => 'flagged', 'checkin_time' => now()->subDays(5)]);
        Attendance::factory()->for($activity)->create(['user_id' => $this->student()->id, 'status' => 'flagged', 'checkin_time' => now()]);
        $banned = User::factory()->create(['role' => 'admin', 'email' => 'gone@srru.ac.th', 'account_status' => 'banned']);

        $this->artisan('app:send-admin-digest')->assertSuccessful();

        Notification::assertSentTo($this->admin, AdminDailyDigest::class, function ($n) {
            $data = $n->toDatabase($this->admin);

            return $data['title_params']['total'] === 2 && $data['body_params']['stale'] === 1;
        });
        Notification::assertNotSentTo($banned, AdminDailyDigest::class);
    }

    public function test_no_digest_on_an_empty_day(): void
    {
        Notification::fake();

        $this->artisan('app:send-admin-digest')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_the_digest_is_scheduled_every_morning(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command ?? '', 'app:send-admin-digest'));

        $this->assertNotNull($event);
        $this->assertSame('0 8 * * *', $event->expression);
    }

    // --- Preferences filter the channels ------------------------------------

    public function test_defaults_send_push_and_email(): void
    {
        $student = $this->student();

        $this->assertSame(['database', FcmChannel::class, 'mail'], (new ActivityCancelled(Activity::factory()->create()))->via($student));
    }

    public function test_turning_a_category_off_keeps_the_bell(): void
    {
        $student = $this->student(['notification_preferences' => ['activity_news' => ['push' => false, 'email' => false]]]);

        $this->assertSame(['database'], (new ActivityCancelled(Activity::factory()->create()))->via($student));
        // Other categories are unaffected.
        $this->assertContains(FcmChannel::class, (new StudentCleared(40, 12))->via($student));
    }

    public function test_quiet_hours_hold_back_push_only_at_night(): void
    {
        $student = $this->student(['notification_preferences' => ['quiet_hours' => true]]);
        $notification = new ActivityCancelled(Activity::factory()->create());

        $this->assertContains(FcmChannel::class, $notification->via($student));

        $this->travelTo(now()->setTime(23, 30));
        $this->assertSame(['database', 'mail'], $notification->via($student));

        $this->travelTo(now()->addDay()->setTime(6, 59));
        $this->assertNotContains(FcmChannel::class, $notification->via($student));
    }

    // --- Settings page ------------------------------------------------------

    public function test_students_see_their_categories(): void
    {
        $this->actingAs($this->student())->get(route('notification-settings.edit'))
            ->assertOk()
            ->assertSee('เตือนเช็คชื่อ')
            ->assertSee('name="checkin_reminders[push]"', false)
            ->assertDontSee('สรุปงานรอตรวจรายวัน');
    }

    public function test_admins_see_their_categories(): void
    {
        $this->actingAs($this->admin)->get(route('notification-settings.edit'))
            ->assertOk()
            ->assertSee('สรุปงานรอตรวจรายวัน')
            ->assertDontSee('name="checkin_reminders[push]"', false);
    }

    public function test_saving_stores_only_known_categories(): void
    {
        $student = $this->student();

        $this->actingAs($student)->put(route('notification-settings.update'), [
            'activity_news' => ['push' => '0', 'email' => '1'],
            'checkin_reminders' => ['push' => '1', 'email' => '1'],
            'admin_digest' => ['push' => '1'],
            'quiet_hours' => '1',
        ])->assertRedirect()->assertSessionHas('status');

        $prefs = $student->fresh()->notification_preferences;
        $this->assertTrue($prefs['quiet_hours']);
        $this->assertSame(['push' => false, 'email' => true], $prefs['activity_news']);
        // Reminders never email, so no email flag is stored for them.
        $this->assertSame(['push' => true], $prefs['checkin_reminders']);
        $this->assertArrayNotHasKey('admin_digest', $prefs);
        // Missing fields mean "off" (the form's hidden 0 inputs).
        $this->assertSame(['push' => false, 'email' => false], $prefs['results']);
    }

    public function test_the_settings_page_needs_login(): void
    {
        $this->get(route('notification-settings.edit'))->assertRedirect();
    }

    public function test_the_bell_links_to_settings(): void
    {
        $this->actingAs($this->student())->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('notification-settings.edit'), false)
            // The panel is teleported to <body>, so closing on an outside
            // click must be checked on the panel, not the bell's wrapper —
            // otherwise any click inside the panel (e.g. the tabs) closes it.
            ->assertSee('@click.outside="if (! $refs.button.contains($event.target)) open = false"', false);
    }
}
