<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityEvaluationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ClearanceReportController extends Controller
{
    public function exportPdf(Request $request, ActivityEvaluationService $evaluator)
    {
        $year = (int) $request->input('year', 4);
        $students = $evaluator->clearedGraduatingStudents($year);

        $pdf = Pdf::loadView('reports.clearance-pdf', [
            'students' => $students,
            'year' => $year,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("clearance-report-year-{$year}-".now()->format('Ymd').'.pdf');
    }
}
