<?php

namespace Tests\Feature\Admin;

use App\Imports\StudentRosterImport;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StudentRosterImportTest extends TestCase
{
    use RefreshDatabase;

    private Faculty $faculty;

    private Major $major;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faculty = Faculty::factory()->create(['name_th' => 'คณะวิทยาศาสตร์และเทคโนโลยี']);
        $this->major = Major::factory()->for($this->faculty)->create(['name_th' => 'วิทยาการคอมพิวเตอร์']);
    }

    private function row(array $overrides = []): array
    {
        return array_merge([
            'รหัสนักศึกษา' => '69010101001',
            'ชื่อ-นามสกุล' => 'สมชาย ใจดี',
            'อีเมล' => 'somchai.j@srru.ac.th',
            'คณะ' => 'คณะวิทยาศาสตร์และเทคโนโลยี',
            'สาขา' => 'วิทยาการคอมพิวเตอร์',
            'ชั้นปี' => 1,
            'ปีที่เข้าศึกษา' => 2569,
            'ประเภทหลักสูตร' => 'ภาคปกติ',
        ], $overrides);
    }

    private function runImport(array $rows): StudentRosterImport
    {
        $import = new StudentRosterImport;
        $import->collection(new Collection($rows));

        return $import;
    }

    public function test_it_creates_a_new_student(): void
    {
        $import = $this->runImport([$this->row()]);

        $this->assertSame(1, $import->createdCount);
        $this->assertSame(0, $import->updatedCount);
        $this->assertDatabaseHas('users', [
            'student_id' => '69010101001',
            'email' => 'somchai.j@srru.ac.th',
            'faculty_id' => $this->faculty->id,
            'major_id' => $this->major->id,
            'role' => 'student',
            'program_type' => 'normal',
        ]);
    }

    public function test_a_special_program_label_maps_to_the_special_program_type(): void
    {
        $this->runImport([$this->row(['ประเภทหลักสูตร' => 'กศ.บป.'])]);

        $this->assertDatabaseHas('users', ['student_id' => '69010101001', 'program_type' => 'special']);
    }

    public function test_it_updates_an_existing_student_matched_by_student_id(): void
    {
        $existing = User::factory()->create([
            'role' => 'student',
            'student_id' => '69010101001',
            'email' => 'old-email@srru.ac.th',
            'name_thai' => 'ชื่อเดิม',
        ]);

        $import = $this->runImport([$this->row(['ชื่อ-นามสกุล' => 'ชื่อใหม่'])]);

        $this->assertSame(0, $import->createdCount);
        $this->assertSame(1, $import->updatedCount);
        $this->assertDatabaseHas('users', ['id' => $existing->id, 'name_thai' => 'ชื่อใหม่', 'email' => 'somchai.j@srru.ac.th']);
    }

    public function test_it_updates_an_existing_student_matched_by_email_when_the_student_id_changed(): void
    {
        $existing = User::factory()->create([
            'role' => 'student',
            'student_id' => '99999999999',
            'email' => 'somchai.j@srru.ac.th',
        ]);

        $import = $this->runImport([$this->row()]); // row's student_id (69010101001) doesn't match, email does

        $this->assertSame(1, $import->updatedCount);
        $this->assertDatabaseHas('users', ['id' => $existing->id, 'student_id' => '69010101001']);
    }

    public function test_a_row_with_a_conflicting_student_id_and_email_is_reported_as_an_error(): void
    {
        User::factory()->create(['role' => 'student', 'student_id' => '69010101001', 'email' => 'a@srru.ac.th']);
        User::factory()->create(['role' => 'student', 'student_id' => '11111111111', 'email' => 'somchai.j@srru.ac.th']);

        $import = $this->runImport([$this->row()]);

        $this->assertSame(0, $import->createdCount);
        $this->assertSame(0, $import->updatedCount);
        $this->assertCount(1, $import->rowErrors);
    }

    public function test_an_invalid_row_is_reported_without_stopping_the_rest_of_the_sheet(): void
    {
        $import = $this->runImport([
            $this->row(['รหัสนักศึกษา' => '123']), // too short -> fails digits:11
            $this->row(['รหัสนักศึกษา' => '69010101002', 'อีเมล' => 'other@srru.ac.th']),
        ]);

        $this->assertSame(1, $import->createdCount);
        $this->assertCount(1, $import->rowErrors);
        $this->assertSame(2, $import->rowErrors[0]['row']); // line 2 = first data row after the heading
    }

    public function test_an_email_outside_the_university_domain_is_rejected(): void
    {
        $import = $this->runImport([$this->row(['อีเมล' => 'someone@gmail.com'])]);

        $this->assertSame(0, $import->createdCount);
        $this->assertCount(1, $import->rowErrors);
    }

    public function test_an_unknown_faculty_name_is_reported_as_an_error(): void
    {
        $import = $this->runImport([$this->row(['คณะ' => 'คณะที่ไม่มีอยู่จริง'])]);

        $this->assertSame(0, $import->createdCount);
        $this->assertCount(1, $import->rowErrors);
    }

    public function test_a_major_not_belonging_to_the_given_faculty_is_reported_as_an_error(): void
    {
        $import = $this->runImport([$this->row(['สาขา' => 'สาขาที่ไม่มีอยู่จริง'])]);

        $this->assertSame(0, $import->createdCount);
        $this->assertCount(1, $import->rowErrors);
        $this->assertStringContainsString('ไม่พบสาขา', $import->rowErrors[0]['message']);
    }

    public function test_a_fully_blank_row_is_skipped_silently(): void
    {
        $blank = array_fill_keys(array_keys($this->row()), '');

        $import = $this->runImport([$blank]);

        $this->assertSame(0, $import->createdCount);
        $this->assertCount(0, $import->rowErrors);
    }
}
