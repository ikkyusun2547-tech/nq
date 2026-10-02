<?php

namespace Tests\Feature\Student;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\CreditTransferRequest;
use App\Models\Faculty;
use App\Models\User;
use App\Services\AcademicYearCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            'faculty_id' => Faculty::factory(),
            'student_id' => '12345678901',
            'year_level' => 2,
            'program_type' => 'normal',
        ]);
    }

    public function test_it_shows_the_position_badge_for_an_approved_current_year_claim(): void
    {
        $student = $this->student();
        CreditTransferRequest::create([
            'user_id' => $student->id,
            'position' => 'class_leader',
            'academic_year' => AcademicYearCalculator::forDate(now()),
            'hours_requested' => 50,
            'activity_category' => 'volunteer',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame('หัวหน้าหมู่เรียน', $response->viewData('currentPositionLabel'));
    }

    public function test_it_hides_the_position_badge_for_a_past_year_claim(): void
    {
        $student = $this->student();
        CreditTransferRequest::create([
            'user_id' => $student->id,
            'position' => 'class_leader',
            'academic_year' => AcademicYearCalculator::forDate(now()) - 1,
            'hours_requested' => 50,
            'activity_category' => 'volunteer',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('currentPositionLabel'));
    }

    public function test_it_hides_the_position_badge_for_a_pending_current_year_claim(): void
    {
        $student = $this->student();
        CreditTransferRequest::create([
            'user_id' => $student->id,
            'position' => 'class_leader',
            'academic_year' => AcademicYearCalculator::forDate(now()),
            'hours_requested' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('currentPositionLabel'));
    }

    public function test_it_shows_no_badge_when_the_student_has_no_credit_transfer_claims(): void
    {
        $student = $this->student();

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $this->assertNull($response->viewData('currentPositionLabel'));
    }

    public function test_it_highlights_activities_open_for_check_in_right_now(): void
    {
        $student = $this->student();
        $open = Activity::factory()->create(['title' => 'Happening Now', 'status' => 'open', 'start_at' => now()->subHour(), 'end_at' => now()->addHours(2)]);
        Activity::factory()->create(['title' => 'Later Today Draft', 'status' => 'draft', 'start_at' => now()->addHours(3), 'end_at' => now()->addHours(5)]);
        $attended = Activity::factory()->create(['title' => 'Already Attended', 'status' => 'open', 'start_at' => now()->subHour(), 'end_at' => now()->addHour()]);
        Attendance::factory()->create(['user_id' => $student->id, 'activity_id' => $attended->id]);

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $this->assertSame([$open->id], $response->viewData('nowActivities')->map(fn ($n) => $n['activity']->id)->all());
        $this->assertSame(['Later Today Draft'], $response->viewData('upcomingActivities')->pluck('title')->all());
        $this->assertCount(7, $response->viewData('week'));
        $response->assertSee('Happening Now')->assertSee(route('checkin.show'));
    }

    public function test_the_now_card_shows_the_activity_banner_when_there_is_one(): void
    {
        $student = $this->student();
        Activity::factory()->create(['status' => 'open', 'start_at' => now()->subHour(), 'end_at' => now()->addHour(), 'banner_url' => 'activity-banners/now.jpg']);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(asset('storage/activity-banners/now.jpg'), false);
    }

    public function test_self_report_activities_count_as_open_only_inside_their_window(): void
    {
        $student = $this->student();
        Activity::factory()->selfReport()->create(['title' => 'Window Open', 'status' => 'open', 'start_at' => now()->subDay(), 'end_at' => now()->addDay()]);
        Activity::factory()->create([
            'title' => 'Window Shut', 'status' => 'open', 'checkin_method' => 'self_report',
            'checkin_opens_at' => now()->subDays(2), 'checkin_closes_at' => now()->subDay(),
            'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $this->assertSame(['Window Open'], $response->viewData('nowActivities')->map(fn ($n) => $n['activity']->title)->all());
    }

    public function test_with_nothing_open_the_next_activity_takes_the_hero_spot_with_a_countdown(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $student = $this->student();
        Activity::factory()->create(['title' => 'Next Up', 'status' => 'open', 'start_at' => now()->addDays(3)->setTime(10, 0), 'end_at' => now()->addDays(3)->setTime(12, 0)]);
        Activity::factory()->create(['title' => 'After That', 'status' => 'open', 'start_at' => now()->addDays(5)->setTime(10, 0), 'end_at' => now()->addDays(5)->setTime(12, 0)]);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('กิจกรรมต่อไป')
            ->assertSee('อีก 3 วัน')
            ->assertDontSee('ไม่มีกิจกรรมที่เปิดเช็คชื่ออยู่ตอนนี้')
            ->assertSeeInOrder(['Next Up', 'After That']);
    }

    public function test_with_nothing_open_or_upcoming_the_hero_shows_progress_and_ways_to_earn_hours(): void
    {
        $this->actingAs($this->student())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ยังไม่มีกิจกรรมที่กำลังจะมาถึง')
            ->assertSee(route('hour-requests.index', ['tab' => 'external']), false)
            ->assertDontSee('ไม่มีกิจกรรมที่เปิดเช็คชื่ออยู่ตอนนี้');
    }

    public function test_with_nothing_upcoming_the_most_recent_missed_activity_takes_the_hero_spot(): void
    {
        $student = $this->student();
        Activity::factory()->closed()->create(['title' => 'Older Miss', 'start_at' => now()->subDays(9), 'end_at' => now()->subDays(9)->addHours(2)]);
        $missed = Activity::factory()->closed()->create(['title' => 'Recent Miss', 'start_at' => now()->subDays(2), 'end_at' => now()->subDays(2)->addHours(2)]);

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $this->assertTrue($response->viewData('missedActivity')->is($missed));
        $response->assertSee('พลาดกิจกรรมนี้')
            ->assertSee(route('late-checkin.show', $missed), false)
            ->assertSee('ขอเช็คชื่อย้อนหลัง');
    }

    public function test_upcoming_beats_missed_for_the_hero_spot(): void
    {
        $student = $this->student();
        Activity::factory()->closed()->create(['start_at' => now()->subDays(2), 'end_at' => now()->subDays(2)->addHours(2)]);
        Activity::factory()->create(['title' => 'Soon', 'status' => 'open', 'start_at' => now()->addDays(2), 'end_at' => now()->addDays(2)->addHours(2)]);

        $response = $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $this->assertNull($response->viewData('missedActivity'));
        $response->assertSee('กิจกรรมต่อไป')->assertDontSee('พลาดกิจกรรมนี้');
    }

    public function test_with_nothing_missed_the_latest_attended_activity_is_shown(): void
    {
        $student = $this->student();
        $attended = Activity::factory()->closed()->create(['title' => 'Went There', 'credit_hours' => 3, 'start_at' => now()->subDays(3), 'end_at' => now()->subDays(3)->addHours(2)]);
        Attendance::factory()->create(['user_id' => $student->id, 'activity_id' => $attended->id, 'checkin_time' => now()->subDays(3)]);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('เข้าร่วมล่าสุด')
            ->assertSee('Went There')
            ->assertSee('+3 ชม.');
    }

    public function test_the_week_strip_is_labelled_with_the_current_month(): void
    {
        // Friday 2 Oct 2026: the Monday-to-Sunday strip starts on 28 Sep.
        $this->travelTo(\Illuminate\Support\Carbon::create(2026, 10, 2, 9));
        app()->setLocale('th');

        $this->actingAs($this->student())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ตุลาคม 2569')
            ->assertDontSee('กันยายน 2569');
    }

    public function test_a_next_activity_tomorrow_reads_tomorrow(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $student = $this->student();
        Activity::factory()->create(['title' => 'Tomorrow Thing', 'status' => 'open', 'start_at' => now()->addDay()->setTime(8, 0), 'end_at' => now()->addDay()->setTime(10, 0)]);

        $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertSee('พรุ่งนี้');
    }
}
