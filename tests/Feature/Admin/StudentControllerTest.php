<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    public function test_a_student_cannot_view_the_student_list(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.students.index'))->assertForbidden();
    }

    public function test_it_sorts_by_year_level_ascending(): void
    {
        $year3 = User::factory()->create(['role' => 'student', 'email' => 'y3@srru.ac.th', 'year_level' => 3, 'student_id' => '10000000003']);
        $year1 = User::factory()->create(['role' => 'student', 'email' => 'y1@srru.ac.th', 'year_level' => 1, 'student_id' => '10000000001']);
        $year2 = User::factory()->create(['role' => 'student', 'email' => 'y2@srru.ac.th', 'year_level' => 2, 'student_id' => '10000000002']);

        $response = $this->actingAs($this->admin())->get(route('admin.students.index', ['sort' => 'year_level', 'dir' => 'asc']));

        $response->assertOk();
        $content = $response->getContent();
        $posY1 = strpos($content, $year1->student_id);
        $posY2 = strpos($content, $year2->student_id);
        $posY3 = strpos($content, $year3->student_id);

        $this->assertTrue($posY1 < $posY2 && $posY2 < $posY3, 'Expected year 1, then 2, then 3 in ascending order.');
    }

    public function test_it_sorts_by_year_level_descending(): void
    {
        $year3 = User::factory()->create(['role' => 'student', 'email' => 'y3@srru.ac.th', 'year_level' => 3, 'student_id' => '10000000003']);
        $year1 = User::factory()->create(['role' => 'student', 'email' => 'y1@srru.ac.th', 'year_level' => 1, 'student_id' => '10000000001']);

        $response = $this->actingAs($this->admin())->get(route('admin.students.index', ['sort' => 'year_level', 'dir' => 'desc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, $year3->student_id) < strpos($content, $year1->student_id));
    }

    public function test_it_sorts_by_student_id(): void
    {
        $b = User::factory()->create(['role' => 'student', 'email' => 'b@srru.ac.th', 'student_id' => '20000000002']);
        $a = User::factory()->create(['role' => 'student', 'email' => 'a@srru.ac.th', 'student_id' => '10000000001']);

        $response = $this->actingAs($this->admin())->get(route('admin.students.index', ['sort' => 'student_id', 'dir' => 'asc']));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertTrue(strpos($content, $a->student_id) < strpos($content, $b->student_id));
    }

    public function test_it_sorts_by_program_type(): void
    {
        $special = User::factory()->create(['role' => 'student', 'email' => 'sp@srru.ac.th', 'student_id' => '30000000003', 'program_type' => 'special']);
        $normal = User::factory()->create(['role' => 'student', 'email' => 'nm@srru.ac.th', 'student_id' => '40000000004', 'program_type' => 'normal']);

        $response = $this->actingAs($this->admin())->get(route('admin.students.index', ['sort' => 'program_type', 'dir' => 'asc']));

        $response->assertOk();
        $content = $response->getContent();

        // 'normal' < 'special' alphabetically
        $this->assertTrue(strpos($content, $normal->student_id) < strpos($content, $special->student_id));
    }

    public function test_an_invalid_sort_value_is_ignored_instead_of_erroring(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.students.index', ['sort' => 'password', 'dir' => 'asc']));

        $response->assertOk();
    }

    public function test_the_default_list_excludes_graduated_students(): void
    {
        $enrolled = User::factory()->create(['role' => 'student', 'email' => 'e@srru.ac.th', 'student_id' => '50000000001']);
        $graduated = User::factory()->create(['role' => 'student', 'email' => 'g@srru.ac.th', 'student_id' => '50000000002', 'graduated_at' => now()]);

        $response = $this->actingAs($this->admin())->get(route('admin.students.index'));

        $response->assertOk();
        $response->assertSee($enrolled->student_id);
        $response->assertDontSee($graduated->student_id);
    }

    public function test_the_enrollment_status_filter_can_show_only_graduated_students(): void
    {
        $enrolled = User::factory()->create(['role' => 'student', 'email' => 'e@srru.ac.th', 'student_id' => '50000000003']);
        $graduated = User::factory()->create(['role' => 'student', 'email' => 'g@srru.ac.th', 'student_id' => '50000000004', 'graduated_at' => now()]);

        $response = $this->actingAs($this->admin())->get(route('admin.students.index', ['enrollment_status' => 'graduated']));

        $response->assertOk();
        $response->assertSee($graduated->student_id);
        $response->assertDontSee($enrolled->student_id);
    }

    public function test_a_graduated_students_profile_can_still_be_viewed(): void
    {
        $graduated = User::factory()->create(['role' => 'student', 'email' => 'g@srru.ac.th', 'graduated_at' => now()]);

        $this->actingAs($this->admin())->get(route('admin.students.show', $graduated))->assertOk();
    }
}
