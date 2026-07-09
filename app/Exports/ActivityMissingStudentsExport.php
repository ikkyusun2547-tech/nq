<?php

namespace App\Exports;

use App\Models\Activity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityMissingStudentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Activity $activity) {}

    public function collection()
    {
        return $this->activity->missingStudentsQuery()
            ->with(['faculty', 'major'])
            ->orderBy('student_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            __('รหัสนักศึกษา'), __('ชื่อ-นามสกุล'), __('คณะ'), __('สาขา'), __('ชั้นปี'),
        ];
    }

    public function map($student): array
    {
        return [
            $student->student_id,
            $student->name_thai ?? $student->name,
            $student->faculty?->name_th,
            $student->major?->name_th,
            $student->current_year,
        ];
    }
}
