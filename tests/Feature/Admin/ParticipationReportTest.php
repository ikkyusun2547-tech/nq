<?php

namespace Tests\Feature\Admin;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_it_downloads_the_faculty_participation_excel_export(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
        $faculty = Faculty::factory()->create();
        User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th', 'faculty_id' => $faculty->id]);

        $response = $this->actingAs($admin)->get(route('admin.reports.faculty-participation'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}
