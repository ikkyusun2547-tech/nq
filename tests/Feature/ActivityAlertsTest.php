<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityNotificationLog;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Models\User;
use App\Notifications\ActivityCancelled;
use App\Notifications\ActivityCreated;
use App\Notifications\ActivityStartingSoon;
use App\Notifications\ActivityUpdated;
use App\Notifications\Announcement;
use App\Notifications\CheckInClosingSoon;
use App\Notifications\CheckInFlagSurge;
use App\Notifications\CheckInOpened;
use App\Notifications\ExternalActivityRequestReviewed;
use App\Notifications\LateCheckInRequestReviewed;
use App\Services\ActivityAlerts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActivityAlertsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->setTime(10, 0));
        $this->admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th', 'account_status' => 'active']);
    }

    private function student(string $tag = ''): User
    {
        return User::factory()->create([
            'role' => 'student', 'email' => 'stu'.$tag.uniqid().'@srru.ac.th', 'faculty_id' => Faculty::factory(),
            'student_id' => (string) random_int(10000000000, 99999999999), 'year_level' => 2, 'program_type' => 'normal',
            'account_status' => 'active',
        ]);
    }

    private function activity(array $attributes = []): Activity
    {
        return Activity::factory()->create(array_merge(['created_by' => $this->admin->id, 'status' => 'open'], $attributes));
    }

    private function runReminders(): void
    {
        $this->artisan('app:send-activity-reminders')->assertSuccessful();
    }

    // --- Channels --------------------------------------------------------

    public function test_email_only_goes_out_for_outcomes_announcements_and_cancellations(): void
    {
        $user = $this->student();
        $activity = $this->activity();

        $this->assertNotContains('mail', (new ActivityCreated($activity))->via($user));
        $this->assertNotContains('mail', (new CheckInOpened($activity))->via($user));
        $this->assertNotContains('mail', (new ActivityStartingSoon($activity))->via($user));
        $this->assertContains('database', (new CheckInOpened($activity))->via($user));

        $this->assertContains('mail', (new ActivityCancelled($activity))->via($user));
        $this->assertContains('mail', (new Announcement('s', 'b'))->via($user));
        $this->assertContains('mail', (new ExternalActivityRequestReviewed(new \App\Models\ExternalActivityRequest(['status' => 'approved'])))->via($user));
        $this->assertContains('mail', (new LateCheckInRequestReviewed(new \App\Models\LateCheckInRequest(['status' => 'approved'])))->via($user));
    }

    // --- Publish / cancel --------------------------------------------------

    public function test_publishing_a_draft_announces_it_once(): void
    {
        $student = $this->student();
        $activity = $this->activity(['status' => 'draft']);

        $this->actingAs($this->admin)->patch(route('admin.activities.update-status', $activity), ['status' => 'open']);
        Notification::assertSentToTimes($student, ActivityCreated::class, 1);

        // Back to draft and live again: no second "new activity".
        $this->actingAs($this->admin)->patch(route('admin.activities.update-status', $activity), ['status' => 'draft']);
        $this->actingAs($this->admin)->patch(route('admin.activities.update-status', $activity), ['status' => 'open']);
        Notification::assertSentToTimes($student, ActivityCreated::class, 1);
    }

    public function test_reopening_a_closed_activity_is_not_announced_as_new(): void
    {
        $student = $this->student();
        $activity = $this->activity(['status' => 'closed']);

        $this->actingAs($this->admin)->patch(route('admin.activities.update-status', $activity), ['status' => 'open']);

        Notification::assertNotSentTo($student, ActivityCreated::class);
    }

    public function test_cancelling_tells_eligible_students_once_and_again_after_a_revival(): void
    {
        $student = $this->student();
        $activity = $this->activity();

        $this->actingAs($this->admin)->patch(route('admin.activities.update-status', $activity), ['status' => 'cancelled']);
        $this->actingAs($this->admin)->patch(route('admin.activities.update-status', $activity), ['status' => 'cancelled']);
        Notification::assertSentToTimes($student, ActivityCancelled::class, 1);

        $this->actingAs($this->admin)->patch(route('admin.activities.update-status', $activity), ['status' => 'open']);
        $this->actingAs($this->admin)->patch(route('admin.activities.update-status', $activity), ['status' => 'cancelled']);
        Notification::assertSentToTimes($student, ActivityCancelled::class, 2);
    }

    public function test_cancelling_through_the_edit_form_sends_cancelled_instead_of_updated(): void
    {
        $student = $this->student();
        $activity = $this->activity();
        $payload = array_merge($activity->only([
            'title', 'activity_level', 'activity_category', 'activity_type', 'academic_year', 'credit_hours', 'capacity',
            'location_name', 'location_lat', 'location_lng', 'allowed_radius', 'checkin_method',
        ]), [
            'status' => 'cancelled',
            'semester' => 1,
            'start_at' => $activity->start_at->copy()->addDay()->format('Y-m-d H:i'), // a "significant" change too
            'end_at' => $activity->end_at->copy()->addDay()->format('Y-m-d H:i'),
        ]);

        $this->actingAs($this->admin)->put(route('admin.activities.update', $activity), $payload)->assertSessionHasNoErrors();

        Notification::assertSentTo($student, ActivityCancelled::class);
        Notification::assertNotSentTo($student, ActivityUpdated::class);
    }

    // --- Check-in opened ---------------------------------------------------

    public function test_qr_check_in_opened_reaches_eligible_students_who_have_not_checked_in_once(): void
    {
        $waiting = $this->student('w');
        $done = $this->student('d');
        $activity = $this->activity(['checkin_method' => 'realtime', 'start_at' => now()->subMinutes(10), 'end_at' => now()->addHours(2)]);
        Attendance::factory()->for($activity)->create(['user_id' => $done->id]);

        $this->runReminders();
        $this->runReminders();

        Notification::assertSentToTimes($waiting, CheckInOpened::class, 1);
        Notification::assertNotSentTo($done, CheckInOpened::class);
        $this->assertDatabaseHas('activity_notification_logs', ['activity_id' => $activity->id, 'kind' => ActivityNotificationLog::CHECK_IN_OPENED]);
    }

    public function test_check_in_opened_ignores_old_starts_drafts_and_cancelled_activities(): void
    {
        $student = $this->student();
        $this->activity(['checkin_method' => 'realtime', 'start_at' => now()->subHours(2), 'end_at' => now()->addHour()]); // started long ago
        $this->activity(['status' => 'draft', 'checkin_method' => 'realtime', 'start_at' => now()->subMinutes(5), 'end_at' => now()->addHour()]);
        $this->activity(['status' => 'cancelled', 'checkin_method' => 'realtime', 'start_at' => now()->subMinutes(5), 'end_at' => now()->addHour()]);
        $this->activity(['checkin_method' => 'realtime', 'start_at' => now()->addMinutes(20), 'end_at' => now()->addHours(3)]); // not yet

        $this->runReminders();

        Notification::assertNotSentTo($student, CheckInOpened::class);
    }

    public function test_self_report_window_opening_links_to_the_evidence_form(): void
    {
        $student = $this->student();
        $activity = $this->activity([
            'checkin_method' => 'self_report', 'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
            'checkin_opens_at' => now()->subMinutes(5), 'checkin_closes_at' => now()->addHours(6),
        ]);

        $this->runReminders();

        Notification::assertSentTo($student, CheckInOpened::class, function (CheckInOpened $n) use ($student, $activity) {
            return $n->toDatabase($student)['url'] === route('self-checkin.show', $activity);
        });
    }

    // --- Self-report closing soon ------------------------------------------

    public function test_closing_soon_reminds_only_students_who_have_not_sent_evidence(): void
    {
        $waiting = $this->student('w');
        $done = $this->student('d');
        $activity = $this->activity([
            'checkin_method' => 'self_report', 'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
            'checkin_opens_at' => now()->subHours(3), 'checkin_closes_at' => now()->addMinutes(40),
        ]);
        Attendance::factory()->for($activity)->create(['user_id' => $done->id, 'checkin_method' => 'self_report']);

        $this->runReminders();
        $this->runReminders();

        Notification::assertSentToTimes($waiting, CheckInClosingSoon::class, 1);
        Notification::assertNotSentTo($done, CheckInClosingSoon::class);
    }

    public function test_short_windows_and_far_off_closings_get_no_closing_reminder(): void
    {
        $student = $this->student();
        // 90-minute window: the "opened" notice already said when it closes.
        $this->activity([
            'checkin_method' => 'self_report', 'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
            'checkin_opens_at' => now()->subMinutes(60), 'checkin_closes_at' => now()->addMinutes(30),
        ]);
        // Closes in 3 hours: too early.
        $this->activity([
            'checkin_method' => 'self_report', 'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
            'checkin_opens_at' => now()->subHours(5), 'checkin_closes_at' => now()->addHours(3),
        ]);

        $this->runReminders();

        Notification::assertNotSentTo($student, CheckInClosingSoon::class);
    }

    // --- Organiser: starting soon --------------------------------------------

    public function test_the_organiser_hears_an_hour_before_start_once(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'admin', 'email' => 'other@srru.ac.th']);
        $this->activity(['start_at' => now()->addMinutes(40), 'end_at' => now()->addHours(3)]);
        $this->activity(['start_at' => now()->addHours(2), 'end_at' => now()->addHours(4)]); // too early

        $this->runReminders();
        $this->runReminders();

        Notification::assertSentToTimes($this->admin, ActivityStartingSoon::class, 1);
        Notification::assertNotSentTo($otherAdmin, ActivityStartingSoon::class);
    }

    public function test_without_an_admin_creator_every_active_admin_is_told(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'super_admin', 'email' => 'other@srru.ac.th']);
        $banned = User::factory()->create(['role' => 'admin', 'email' => 'banned@srru.ac.th', 'account_status' => 'banned']);
        $formerAdmin = User::factory()->create(['role' => 'student', 'email' => 'former@srru.ac.th']);
        $this->activity(['created_by' => $formerAdmin->id, 'start_at' => now()->addMinutes(30), 'end_at' => now()->addHours(2)]);

        $this->runReminders();

        Notification::assertSentTo($this->admin, ActivityStartingSoon::class);
        Notification::assertSentTo($otherAdmin, ActivityStartingSoon::class);
        Notification::assertNotSentTo($banned, ActivityStartingSoon::class);
        Notification::assertNotSentTo($formerAdmin, ActivityStartingSoon::class);
    }

    // --- Organiser: GPS flag surge -------------------------------------------

    private function qrCheckIns(Activity $activity, int $gpsFlagged, int $ok): Attendance
    {
        Attendance::factory()->for($activity)->count($ok)->create(['checkin_method' => 'realtime']);
        $last = null;
        for ($i = 0; $i < $gpsFlagged; $i++) {
            $last = Attendance::factory()->for($activity)->flagged('GPS_OUT_OF_BOUNDS')->create(['checkin_method' => 'realtime']);
        }

        return $last;
    }

    public function test_a_surge_of_gps_flags_alerts_the_organiser_once(): void
    {
        $activity = $this->activity();
        $last = $this->qrCheckIns($activity, gpsFlagged: 5, ok: 5); // 50%

        app(ActivityAlerts::class)->afterCheckIn($last);
        app(ActivityAlerts::class)->afterCheckIn($last);

        Notification::assertSentToTimes($this->admin, CheckInFlagSurge::class, 1);
    }

    public function test_a_few_flags_or_a_small_share_is_not_a_surge(): void
    {
        $few = $this->activity();
        app(ActivityAlerts::class)->afterCheckIn($this->qrCheckIns($few, gpsFlagged: 4, ok: 0)); // only 4

        $small = $this->activity();
        app(ActivityAlerts::class)->afterCheckIn($this->qrCheckIns($small, gpsFlagged: 5, ok: 25)); // ~17%

        Notification::assertNotSentTo($this->admin, CheckInFlagSurge::class);
    }

    public function test_the_reminders_command_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('app:send-activity-reminders')->assertSuccessful();
    }
}
