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
            __('รหัสนักศึกษา'), __('ชื่อ-นามสกุล'), __('คณะ'), __('สาขา'), __('ชั้นปี'),
            __('เวลาเช็คชื่อ'), __('ระยะห่าง (เมตร)'), __('สถานะ'), __('หมายเหตุ'),
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
            ['auto_approved' => __('ผ่านอัตโนมัติ'), 'flagged' => __('ติดธงแดง'), 'rejected' => __('ปฏิเสธ')][$attendance->status],
            $attendance->flag_reason,
        ];
    }
}
