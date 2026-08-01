<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtRiskStudentsReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    /** Special-program (กศ.บป.) criteria: 4 activities, 50 hours — cheaper to satisfy in a test than the 25/100 normal track. */
    private function studentWithHours(int $yearLevel, int $activityCount, int $hoursEach): User
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'stu'.uniqid().'@srru.ac.th',
            // UserFactory doesn't set student_id at all (stays null unless
            // given explicitly) — a distinct one per call so tests can tell
            // rows apart in the rendered output instead of every student
            // silently sharing an empty string.
            'student_id' => (string) random_int(10000000000, 99999999999),
            'year_level' => $yearLevel,
            'program_type' => 'special',
            'enrollment_year' => 2565,
        ]);

        for ($i = 0; $i < $activityCount; $i++) {
            $activity = Activity::factory()->create(['credit_hours' => $hoursEach]);
            Attendance::factory()->for($activity)->for($student)->create();
        }

        return $student;
    }

    public function test_a_student_short_on_hours_appears_in_the_not_cleared_list(): void
    {
        $student = $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 5); // 20 hours < 50

        $notCleared = app(ActivityEvaluationService::class)->notClearedGraduatingStudents(4);

        $row = $notCleared->firstWhere('user.id', $student->id);
        $this->assertNotNull($row);
        $this->assertSame(30, $row['hours_remaining']); // 50 - 20
    }

    public function test_a_cleared_student_does_not_appear_in_the_not_cleared_list(): void
    {
        $student = $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 15); // 60 hours >= 50

        $notCleared = app(ActivityEvaluationService::class)->notClearedGraduatingStudents(4);

        $this->assertFalse($notCleared->pluck('user.id')->contains($student->id));
    }

    public function test_a_student_cannot_view_the_report(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.reports.at-risk'))->assertForbidden();
    }

    public function test_it_shows_not_cleared_students_on_the_web_view(): void
    {
        $student = $this->studentWithHours(yearLevel: 4, activityCount: 1, hoursEach: 5);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.at-risk'));

        $response->assertOk();
        $response->assertSee($student->student_id);
    }

    public function test_the_web_view_paginates_at_twenty_students_per_page(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->studentWithHours(yearLevel: 4, activityCount: 1, hoursEach: 5); // each short by 45 hours
        }

        $admin = $this->admin();
        $page1 = $this->actingAs($admin)->get(route('admin.reports.at-risk'));
        $page2 = $this->actingAs($admin)->get(route('admin.reports.at-risk', ['page' => 2]));

        $rowMarker = 'border-b border-slate-100 last:border-0';

        $page1->assertOk();
        $page2->assertOk();
        $this->assertSame(20, substr_count($page1->getContent(), $rowMarker));
        $this->assertSame(5, substr_count($page2->getContent(), $rowMarker));
        $page1->assertSee('25'); // total-not-cleared stat tile reflects the full count, not just this page
    }

    public function test_it_downloads_the_at_risk_report_as_a_pdf(): void
    {
        $this->studentWithHours(yearLevel: 4, activityCount: 1, hoursEach: 5);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.at-risk-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_an_explicit_sort_overrides_the_default_hours_remaining_ordering(): void
    {
        $fewerHours = $this->studentWithHours(yearLevel: 4, activityCount: 1, hoursEach: 5); // 5 hours, 45 remaining (default: shown first)
        $moreHours = $this->studentWithHours(yearLevel: 4, activityCount: 1, hoursEach: 40); // 40 hours, 10 remaining (default: shown second)

        // Default order (hours_remaining asc) would put $moreHours first — sorting
        // by total_hours descending instead should flip that.
        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.at-risk', ['sort' => 'total_hours', 'dir' => 'desc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, $moreHours->student_id) < strpos($content, $fewerHours->student_id));
    }
}
