<?php

namespace Tests\Feature\Admin;

use App\Models\Faculty;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParticipationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_cannot_view_reports(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_the_reports_hub_loads(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
    }

    public function test_it_shows_faculty_participation_on_the_web_view(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $faculty = Faculty::factory()->create();
        User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th', 'faculty_id' => $faculty->id]);

        $response = $this->actingAs($admin)->get(route('admin.reports.faculty-participation'));

        $response->assertOk();
        $response->assertSee($faculty->name_th);
    }

    /**
     * facultyParticipationSummary() used to call summarize() once per
     * student (7-8 queries each) — this proves the bulk-query rewrite:
     * going from 3 students to 23 shouldn't meaningfully change the query
     * count. A lingering per-student N+1 would roughly 8x it instead.
     */
    public function test_the_summary_query_count_does_not_scale_with_student_count(): void
    {
        $faculty = Faculty::factory()->create();
        $evaluator = app(ActivityEvaluationService::class);

        User::factory()->count(3)->create(['role' => 'student', 'faculty_id' => $faculty->id]);
        DB::enableQueryLog();
        $evaluator->facultyParticipationSummary();
        $queriesForThree = count(DB::getQueryLog());
        DB::flushQueryLog();

        User::factory()->count(20)->create(['role' => 'student', 'faculty_id' => $faculty->id]);
        DB::flushQueryLog(); // discard the 20 inserts above — only the summary call itself should be measured
        $evaluator->facultyParticipationSummary();
        $queriesForTwentyThree = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($queriesForThree + 2, $queriesForTwentyThree);
    }

    public function test_it_downloads_the_faculty_participation_excel_export(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $faculty = Faculty::factory()->create();
        User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th', 'faculty_id' => $faculty->id]);

        $response = $this->actingAs($admin)->get(route('admin.reports.faculty-participation-excel'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}
