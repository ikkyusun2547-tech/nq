<?php

namespace App\Exports;

use App\Models\Faculty;
use App\Services\ActivityEvaluationService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * The one report that existed before this (ClearanceReportController) only
 * covers year-4 students who already cleared. This is the general-purpose
 * one: every faculty's participation at a glance, for whichever academic
 * year the registrar/dean's office is asking about.
 */
class FacultyParticipationExport implements FromCollection, WithHeadings
{
    public function __construct(protected ActivityEvaluationService $evaluator)
    {
    }

    public function collection()
    {
        return Faculty::with(['users' => fn ($q) => $q->where('role', 'student')])
            ->orderBy('name_th')
            ->get()
            ->map(function (Faculty $faculty) {
                $students = $faculty->users;
                $summaries = $students->map(fn ($student) => $this->evaluator->summarize($student));

                $studentCount = $students->count();
                $clearedCount = $summaries->where('is_cleared', true)->count();
                $avgHours = $studentCount > 0 ? round($summaries->avg('total_hours'), 1) : 0;
                $avgActivities = $studentCount > 0 ? round($summaries->avg('total_activities'), 1) : 0;

                return [
                    $faculty->name_th,
                    $studentCount,
                    $clearedCount,
                    $studentCount > 0 ? round($clearedCount / $studentCount * 100, 1) : 0,
                    $avgHours,
                    $avgActivities,
                ];
            });
    }

    public function headings(): array
    {
        return [
            __('คณะ'), __('จำนวนนักศึกษา'), __('ผ่านเกณฑ์แล้ว'), __('ร้อยละที่ผ่านเกณฑ์'),
            __('ชั่วโมงเฉลี่ย/คน'), __('กิจกรรมเฉลี่ย/คน'),
        ];
    }
}
