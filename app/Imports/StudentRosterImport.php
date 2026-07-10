<?php

namespace App\Imports;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\User;
use App\Services\AcademicYearCalculator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class StudentRosterImport implements ToCollection, WithHeadingRow
{
    public const REQUIRED_HEADINGS = [
        'รหัสนักศึกษา', 'ชื่อ-นามสกุล', 'อีเมล', 'คณะ', 'สาขา', 'ชั้นปี', 'ปีที่เข้าศึกษา', 'ประเภทหลักสูตร',
    ];

    public int $createdCount = 0;

    public int $updatedCount = 0;

    /** @var array<int, array{row: int, message: string}> */
    public array $rowErrors = [];

    public function __construct()
    {
        // The default 'slug' formatter runs headers through Str::ascii(),
        // which has no transliteration table for Thai script and silently
        // drops it — every heading collapses to '' and $row['รหัสนักศึกษา']
        // would never resolve. 'none' keeps the literal header text.
        HeadingRowFormatter::default('none');
    }

    public function collection(SupportCollection $rows)
    {
        $emailDomain = config('services.srru.email_domain');
        $facultyIdsByName = Faculty::pluck('id', 'name_th');
        $majors = Major::get(['id', 'faculty_id', 'name_th']);

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2; // 0-indexed + the stripped heading row

            $data = [
                'student_id' => trim((string) ($row['รหัสนักศึกษา'] ?? '')),
                'name_thai' => trim((string) ($row['ชื่อ-นามสกุล'] ?? '')),
                'email' => trim(strtolower((string) ($row['อีเมล'] ?? ''))),
                'faculty_name' => trim((string) ($row['คณะ'] ?? '')),
                'major_name' => trim((string) ($row['สาขา'] ?? '')),
                'year_level' => trim((string) ($row['ชั้นปี'] ?? '')),
                'enrollment_year' => trim((string) ($row['ปีที่เข้าศึกษา'] ?? '')),
                'program_label' => trim((string) ($row['ประเภทหลักสูตร'] ?? '')),
            ];

            // Trailing blank rows in the sheet are not errors, just noise.
            if (collect($data)->every(fn ($value) => $value === '')) {
                continue;
            }

            $validator = Validator::make($data, [
                'student_id' => ['required', 'digits:11'],
                'name_thai' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'ends_with:@'.$emailDomain],
                'faculty_name' => ['required', Rule::in($facultyIdsByName->keys()->all())],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'enrollment_year' => ['required', 'integer', 'min:2540', 'max:'.AcademicYearCalculator::forDate(now())],
                'program_label' => ['required', Rule::in(['ภาคปกติ', 'กศ.บป.'])],
            ]);

            if ($validator->fails()) {
                $this->rowErrors[] = ['row' => $lineNumber, 'message' => $validator->errors()->first()];

                continue;
            }

            $facultyId = $facultyIdsByName[$data['faculty_name']];
            $major = $majors->first(fn ($m) => $m->faculty_id === $facultyId && $m->name_th === $data['major_name']);

            if (! $major) {
                $this->rowErrors[] = [
                    'row' => $lineNumber,
                    'message' => __('ไม่พบสาขา ":major" ในคณะ ":faculty"', ['major' => $data['major_name'], 'faculty' => $data['faculty_name']]),
                ];

                continue;
            }

            $existingByStudentId = User::where('student_id', $data['student_id'])->first();
            $existingByEmail = User::where('email', $data['email'])->first();

            if ($existingByStudentId && $existingByEmail && $existingByStudentId->id !== $existingByEmail->id) {
                $this->rowErrors[] = [
                    'row' => $lineNumber,
                    'message' => __('รหัสนักศึกษาและอีเมลตรงกับคนละบัญชีที่มีอยู่แล้วในระบบ'),
                ];

                continue;
            }

            $target = $existingByStudentId ?? $existingByEmail;

            $attributes = [
                'student_id' => $data['student_id'],
                'name_thai' => $data['name_thai'],
                'email' => $data['email'],
                'faculty_id' => $facultyId,
                'major_id' => $major->id,
                'year_level' => (int) $data['year_level'],
                'enrollment_year' => (int) $data['enrollment_year'],
                'program_type' => $data['program_label'] === 'กศ.บป.' ? 'special' : 'normal',
                'role' => 'student',
            ];

            if ($target) {
                $target->update($attributes);
                $this->updatedCount++;
            } else {
                User::create([...$attributes, 'name' => $data['name_thai']]);
                $this->createdCount++;
            }
        }

        HeadingRowFormatter::reset();
    }
}
