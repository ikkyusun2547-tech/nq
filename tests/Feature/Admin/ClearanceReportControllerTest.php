<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearanceReportControllerTest extends TestCase
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
            'enrollment_year' => 2565, // clearedGraduatingStudents() requires this to be non-null
        ]);

        for ($i = 0; $i < $activityCount; $i++) {
            $activity = Activity::factory()->create(['credit_hours' => $hoursEach]);
            Attendance::factory()->for($activity)->for($student)->create();
        }

        return $student;
    }

    // --- service-level: the actual clearance logic ---

    public function test_a_student_who_meets_both_thresholds_is_cleared(): void
    {
        $student = $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 15); // 60 hours >= 50, 4 >= 4

        $cleared = app(ActivityEvaluationService::class)->clearedGraduatingStudents(4);

        $this->assertTrue($cleared->pluck('user.id')->contains($student->id));
    }

    public function test_a_student_short_on_hours_is_excluded_even_with_enough_activities(): void
    {
        $student = $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 5); // 20 hours < 50

        $cleared = app(ActivityEvaluationService::class)->clearedGraduatingStudents(4);

        $this->assertFalse($cleared->pluck('user.id')->contains($student->id));
    }

    public function test_a_student_in_a_different_year_is_excluded_from_the_year_4_report(): void
    {
        $student = $this->studentWithHours(yearLevel: 2, activityCount: 4, hoursEach: 15);

        $cleared = app(ActivityEvaluationService::class)->clearedGraduatingStudents(4);

        $this->assertFalse($cleared->pluck('user.id')->contains($student->id));
    }

    public function test_a_graduated_student_is_excluded_from_the_year_4_report_even_if_otherwise_cleared(): void
    {
        $student = $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 15); // 60 hours >= 50, 4 >= 4
        $student->update(['graduated_at' => now()]);

        $cleared = app(ActivityEvaluationService::class)->clearedGraduatingStudents(4);

        $this->assertFalse($cleared->pluck('user.id')->contains($student->id));
    }

    // --- controller: authorization + response wiring ---

    public function test_a_student_cannot_view_the_clearance_report(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.reports.clearance'))->assertForbidden();
    }

    public function test_it_shows_cleared_students_on_the_web_view(): void
    {
        $student = $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 15);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.clearance'));

        $response->assertOk();
        $response->assertSee($student->student_id);
    }

    public function test_the_web_view_accepts_a_year_query_parameter(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.reports.clearance', ['year' => 2]));

        $response->assertOk();
    }

    public function test_the_web_view_paginates_at_twenty_students_per_page(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 15);
        }

        $admin = $this->admin();
        $page1 = $this->actingAs($admin)->get(route('admin.reports.clearance'));
        $page2 = $this->actingAs($admin)->get(route('admin.reports.clearance', ['page' => 2]));

        // Table rows share a class string not used by the <thead> row, so
        // counting it is a reliable proxy for "how many students rendered".
        $rowMarker = 'border-b border-slate-100 last:border-0';

        $page1->assertOk();
        $page2->assertOk();
        $this->assertSame(20, substr_count($page1->getContent(), $rowMarker));
        $this->assertSame(5, substr_count($page2->getContent(), $rowMarker));
        $page1->assertSee('25'); // total-cleared stat tile reflects the full count, not just this page
    }

    public function test_a_student_cannot_download_the_clearance_pdf(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.reports.clearance-pdf'))->assertForbidden();
    }

    public function test_it_downloads_the_clearance_report_as_a_pdf(): void
    {
        $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 15);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.clearance-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_the_pdf_export_accepts_a_year_query_parameter(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.reports.clearance-pdf', ['year' => 2]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_it_sorts_by_total_hours_descending(): void
    {
        $fewer = $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 15); // 60 hours
        $more = $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 30); // 120 hours

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.clearance', ['sort' => 'total_hours', 'dir' => 'desc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, $more->student_id) < strpos($content, $fewer->student_id));
    }
}
