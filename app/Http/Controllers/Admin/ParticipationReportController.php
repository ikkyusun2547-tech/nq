<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FacultyParticipationExport;
use App\Http\Controllers\Controller;
use App\Services\ActivityEvaluationService;
use Maatwebsite\Excel\Facades\Excel;

class ParticipationReportController extends Controller
{
    public function index(ActivityEvaluationService $evaluator)
    {
        $rows = $evaluator->facultyParticipationSummary();

        return view('admin.reports.faculty-participation', compact('rows'));
    }

    public function exportExcel(ActivityEvaluationService $evaluator)
    {
        return Excel::download(
            new FacultyParticipationExport($evaluator),
            'faculty-participation-'.now()->format('Ymd').'.xlsx'
        );
    }
}
