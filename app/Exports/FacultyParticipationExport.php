<?php

namespace App\Exports;

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
        return $this->evaluator->facultyParticipationSummary()->map(fn (array $row) => [
            $row['faculty']->name_th,
            $row['student_count'],
            $row['cleared_count'],
            $row['cleared_pct'],
            $row['avg_hours'],
            $row['avg_activities'],
        ]);
    }

    public function headings(): array
    {
        return [
            __('คณะ'), __('จำนวนนักศึกษา'), __('ผ่านเกณฑ์แล้ว'), __('ร้อยละที่ผ่านเกณฑ์'),
            __('ชั่วโมงเฉลี่ย/คน'), __('กิจกรรมเฉลี่ย/คน'),
        ];
    }
}
