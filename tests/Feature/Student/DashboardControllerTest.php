<?php

namespace Tests\Feature\Student;

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
}
