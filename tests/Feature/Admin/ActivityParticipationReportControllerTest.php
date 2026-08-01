<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityParticipationReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_computes_the_participation_percentage_against_eligible_students(): void
    {
        // Explicit created_by — ActivityFactory's implicit `User::factory()`
        // default otherwise adds an extra role='student' row (the users
        // table's DB-level default) that would silently inflate the
        // "eligible students" count below.
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $activity = Activity::factory()->create(['title' => 'กิจกรรมทดสอบ', 'created_by' => $admin->id]);
        User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        $checkedIn = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th']);
        Attendance::factory()->for($activity)->for($checkedIn)->create();

        $response = $this->actingAs($admin)->get(route('admin.reports.activity-participation'));

        $response->assertOk();
        $response->assertSee('กิจกรรมทดสอบ');
        $response->assertSee('50%', false);
    }

    public function test_a_graduated_student_does_not_count_as_eligible(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $activity = Activity::factory()->create(['title' => 'กิจกรรมทดสอบสอง', 'created_by' => $admin->id]);
        $checkedIn = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        Attendance::factory()->for($activity)->for($checkedIn)->create();
        User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th', 'graduated_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.reports.activity-participation'));

        $response->assertOk();
        $response->assertSee('กิจกรรมทดสอบสอง');
        // Only the one checked-in (non-graduated) student is eligible, so participation is 100%.
        $response->assertSee('100%', false);
    }

    public function test_a_student_cannot_view_the_report(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.reports.activity-participation'))->assertForbidden();
    }

    public function test_it_sorts_by_title_ascending_via_a_plain_db_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        Activity::factory()->create(['title' => 'Zebra', 'created_by' => $admin->id]);
        Activity::factory()->create(['title' => 'Alpha', 'created_by' => $admin->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.activity-participation', ['sort' => 'title', 'dir' => 'asc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, 'Alpha') < strpos($content, 'Zebra'));
    }

    public function test_it_sorts_by_participation_percentage_computed_across_the_whole_dataset(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $low = Activity::factory()->create(['title' => 'Low Turnout', 'created_by' => $admin->id]);
        $high = Activity::factory()->create(['title' => 'High Turnout', 'created_by' => $admin->id]);
        User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th']);
        $checkedIn = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th']);
        Attendance::factory()->for($high)->for($checkedIn)->create(); // High Turnout: 1/2 = 50%, Low Turnout: 0/2 = 0%

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.activity-participation', ['sort' => 'participation_pct', 'dir' => 'desc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, 'High Turnout') < strpos($content, 'Low Turnout'));
    }

    public function test_it_sorts_by_eligible_count_computed_across_the_whole_dataset(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $faculty = \App\Models\Faculty::factory()->create();
        User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th', 'faculty_id' => $faculty->id]);
        User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th']); // different/no faculty

        $open = Activity::factory()->create(['title' => 'Open To Everyone', 'created_by' => $admin->id]); // eligible: both students
        $restricted = Activity::factory()->create(['title' => 'Faculty Restricted', 'created_by' => $admin->id]);
        \App\Models\ActivityRestriction::create(['activity_id' => $restricted->id, 'faculty_id' => $faculty->id]); // eligible: only the one student

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.activity-participation', ['sort' => 'eligible_count', 'dir' => 'desc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, 'Open To Everyone') < strpos($content, 'Faculty Restricted'));
    }

    public function test_it_downloads_the_excel_export(): void
    {
        Activity::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);

        $response = $this->actingAs($admin)->get(route('admin.reports.activity-participation-excel'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}
