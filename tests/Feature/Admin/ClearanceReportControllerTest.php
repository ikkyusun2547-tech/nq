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

    // --- controller: authorization + response wiring ---

    public function test_a_student_cannot_download_the_clearance_report(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.reports.clearance'))->assertForbidden();
    }

    public function test_it_downloads_the_clearance_report_as_a_pdf(): void
    {
        $this->studentWithHours(yearLevel: 4, activityCount: 4, hoursEach: 15);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.clearance'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_it_accepts_a_year_query_parameter(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.reports.clearance', ['year' => 2]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
