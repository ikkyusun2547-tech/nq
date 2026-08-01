<?php

namespace App\Exports;

use App\Models\Activity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ActivityParticipationExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Activity::query()
            ->withCount('attendances')
            ->orderByDesc('start_at')
            ->get()
            ->map(function (Activity $activity) {
                $eligible = $activity->eligibleStudentsCount();
                $pct = $eligible > 0 ? round($activity->attendances_count / $eligible * 100, 1) : null;

                return [
                    $activity->title,
                    $activity->start_at?->format('d/m/Y'),
                    $activity->activity_category,
                    $eligible,
                    $activity->attendances_count,
                    $pct,
                ];
            });
    }

    public function headings(): array
    {
        return [
            __('ชื่อกิจกรรม'), __('วันที่'), __('หมวดหมู่'),
            __('มีสิทธิ์เข้าร่วม'), __('เช็คชื่อแล้ว'), __('ร้อยละ'),
        ];
    }
}
