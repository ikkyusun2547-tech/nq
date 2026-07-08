<?php

namespace App\Exports;

use App\Models\Activity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ActivityAttendeesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Activity $activity) {}

    public function collection()
    {
        return $this->activity->attendances()
            ->with(['user.faculty', 'user.major'])
            ->orderBy('checkin_time')
            ->get();
    }

    public function headings(): array
    {
        return [
            'รหัสนักศึกษา', 'ชื่อ-นามสกุล', 'คณะ', 'สาขา', 'ชั้นปี',
            'เวลาเช็กชื่อ', 'ระยะห่าง (เมตร)', 'สถานะ', 'หมายเหตุ',
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->user->student_id,
            $attendance->user->name_thai ?? $attendance->user->name,
            $attendance->user->faculty?->name_th,
            $attendance->user->major?->name_th,
            $attendance->user->current_year,
            $attendance->checkin_time->format('d/m/Y H:i:s'),
            $attendance->distance_meters,
            ['auto_approved' => 'ผ่านอัตโนมัติ', 'flagged' => 'ติดธงแดง', 'rejected' => 'ปฏิเสธ'][$attendance->status],
            $attendance->flag_reason,
        ];
    }
}
