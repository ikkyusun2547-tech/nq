<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityRestriction;
use App\Models\Attendance;
use App\Models\Faculty;
use App\Models\User;
use App\Services\ActivityCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    }

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

    private function activityOn(string $start, string $end, array $attributes = []): Activity
    {
        return Activity::factory()->create(['start_at' => $start, 'end_at' => $end, ...$attributes]);
    }

    public function test_the_grid_is_whole_monday_to_sunday_weeks(): void
    {
        $weeks = ActivityCalendar::weeks(CarbonImmutable::parse('2026-10-01'), collect());

        $this->assertSame('2026-09-28', $weeks[0][0]['key']); // Monday before Oct 1st
        $this->assertSame('2026-11-01', end($weeks)[6]['key']); // Sunday after Oct 31st
        foreach ($weeks as $week) {
            $this->assertCount(7, $week);
        }
    }

    public function test_multi_day_activities_appear_on_every_day_they_run_but_not_past_midnight_end(): void
    {
        $activity = $this->activityOn('2026-10-10 09:00', '2026-10-13 00:00');

        $days = collect(ActivityCalendar::weeks(CarbonImmutable::parse('2026-10-01'), collect([$activity])))
            ->flatten(1)
            ->filter(fn ($day) => $day['activities']->isNotEmpty())
            ->pluck('key')
            ->values()
            ->all();

        $this->assertSame(['2026-10-10', '2026-10-11', '2026-10-12'], $days);
    }

    public function test_students_see_eligible_activities_for_the_month_only(): void
    {
        $student = $this->student();
        $this->activityOn('2026-10-20 09:00', '2026-10-20 12:00', ['title' => 'Visible Activity']);
        $this->activityOn('2026-10-21 09:00', '2026-10-21 12:00', ['title' => 'Cancelled Activity', 'status' => 'cancelled']);
        $this->activityOn('2026-12-01 09:00', '2026-12-01 12:00', ['title' => 'Later Activity']);
        $restricted = $this->activityOn('2026-10-22 09:00', '2026-10-22 12:00', ['title' => 'Other Faculty Activity']);
        ActivityRestriction::create(['activity_id' => $restricted->id, 'faculty_id' => Faculty::factory()->create()->id]);

        $this->actingAs($student)->get(route('activities.calendar'))
            ->assertOk()
            ->assertSee('Visible Activity')
            ->assertDontSee('Cancelled Activity')
            ->assertDontSee('Later Activity')
            ->assertDontSee('Other Faculty Activity');
    }

    public function test_the_month_query_navigates_and_bad_values_fall_back_to_this_month(): void
    {
        $student = $this->student();
        $this->activityOn('2026-12-01 09:00', '2026-12-01 12:00', ['title' => 'December Activity']);

        $this->actingAs($student)->get(route('activities.calendar', ['month' => '2026-12']))
            ->assertOk()
            ->assertSee('December Activity');

        $this->actingAs($student)->get(route('activities.calendar', ['month' => 'garbage']))
            ->assertOk()
            ->assertSee('month=2026-11', false) // "next" link from October
            ->assertDontSee('December Activity');
    }

    public function test_checked_in_activities_are_marked(): void
    {
        $student = $this->student();
        $activity = $this->activityOn('2026-10-20 09:00', '2026-10-20 12:00');
        Attendance::factory()->create(['user_id' => $student->id, 'activity_id' => $activity->id]);

        $this->actingAs($student)->get(route('activities.calendar'))
            ->assertOk()
            ->assertSee(__('เช็คชื่อแล้ว'));
    }

    public function test_admins_see_drafts_and_cancelled_activities_linking_to_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $draft = $this->activityOn('2026-10-20 09:00', '2026-10-20 12:00', ['title' => 'Draft Activity', 'status' => 'draft']);
        $this->activityOn('2026-10-21 09:00', '2026-10-21 12:00', ['title' => 'Cancelled Activity', 'status' => 'cancelled']);

        $this->actingAs($admin)->get(route('admin.activities.calendar'))
            ->assertOk()
            ->assertSee('Draft Activity')
            ->assertSee('Cancelled Activity')
            ->assertSee(route('admin.activities.edit', $draft));
    }

    public function test_students_cannot_open_the_admin_calendar(): void
    {
        $this->actingAs($this->student())->get(route('admin.activities.calendar'))->assertForbidden();
    }

    public function test_the_detail_page_offers_a_google_calendar_link_for_upcoming_activities(): void
    {
        $student = $this->student();
        $upcoming = $this->activityOn('2026-10-20 09:00', '2026-10-20 12:00', ['title' => 'Upcoming']);
        $past = $this->activityOn('2026-10-01 09:00', '2026-10-01 12:00', ['status' => 'closed']);

        $this->assertStringContainsString('dates=20261020T090000%2F20261020T120000', $upcoming->googleCalendarUrl());
        $this->assertStringContainsString('ctz=Asia%2FBangkok', $upcoming->googleCalendarUrl());

        $this->actingAs($student)->get(route('activities.show', $upcoming))->assertSee('calendar.google.com', false);
        $this->actingAs($student)->get(route('activities.show', $past))->assertDontSee('calendar.google.com', false);
    }
}
