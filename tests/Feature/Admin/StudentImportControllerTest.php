<?php

namespace Tests\Feature\Admin;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class StudentImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email' => 'admin@srru.ac.th']);
    }

    private function rosterFile(array $rows): UploadedFile
    {
        $export = new class($rows) implements FromArray, WithHeadings
        {
            public function __construct(private array $rows) {}

            public function headings(): array
            {
                return ['รหัสนักศึกษา', 'ชื่อ-นามสกุล', 'อีเมล', 'คณะ', 'สาขา', 'ชั้นปี', 'ปีที่เข้าศึกษา', 'ประเภทหลักสูตร'];
            }

            public function array(): array
            {
                return $this->rows;
            }
        };

        $path = 'roster-'.uniqid().'.xlsx';
        Excel::store($export, $path, 'local');

        return new UploadedFile(storage_path('app/private/'.$path), 'roster.xlsx', null, null, true);
    }

    public function test_a_student_cannot_view_the_import_form(): void
    {
        $student = User::factory()->create(['role' => 'student', 'email' => 'stu@srru.ac.th']);

        $this->actingAs($student)->get(route('admin.students.import.create'))->assertForbidden();
    }

    public function test_the_import_form_loads(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.students.import.create'))
            ->assertOk();
    }

    public function test_it_downloads_the_import_template(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.students.import.template'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    public function test_it_requires_a_roster_file(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.students.import.store'), [])
            ->assertSessionHasErrors('roster_file');
    }

    public function test_it_imports_a_valid_roster_and_reports_the_results(): void
    {
        $faculty = Faculty::factory()->create(['name_th' => 'คณะวิทยาศาสตร์และเทคโนโลยี']);
        Major::factory()->for($faculty)->create(['name_th' => 'วิทยาการคอมพิวเตอร์']);

        $file = $this->rosterFile([
            ['69010101001', 'สมชาย ใจดี', 'somchai.j@srru.ac.th', 'คณะวิทยาศาสตร์และเทคโนโลยี', 'วิทยาการคอมพิวเตอร์', 1, 2569, 'ภาคปกติ'],
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.students.import.store'), [
            'roster_file' => $file,
        ]);

        $response->assertRedirect(route('admin.students.import.create'));
        $response->assertSessionHas('import_result', function ($result) {
            return $result['created'] === 1 && $result['updated'] === 0 && empty($result['errors']);
        });
        $this->assertDatabaseHas('users', ['student_id' => '69010101001', 'email' => 'somchai.j@srru.ac.th']);
    }

    public function test_it_reports_row_errors_without_a_500(): void
    {
        $file = $this->rosterFile([
            ['123', 'ชื่อไม่สมบูรณ์', 'bad@gmail.com', 'คณะที่ไม่มีอยู่จริง', 'สาขาไม่มีอยู่จริง', 1, 2569, 'ภาคปกติ'],
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.students.import.store'), [
            'roster_file' => $file,
        ]);

        $response->assertRedirect(route('admin.students.import.create'));
        $response->assertSessionHas('import_result', function ($result) {
            return $result['created'] === 0 && count($result['errors']) === 1;
        });
    }
}
