<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FacultyParticipationExport;
use App\Http\Controllers\Controller;
use App\Services\ActivityEvaluationService;
use Maatwebsite\Excel\Facades\Excel;

class ParticipationReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function exportExcel(ActivityEvaluationService $evaluator)
    {
        return Excel::download(
            new FacultyParticipationExport($evaluator),
            'faculty-participation-'.now()->format('Ymd').'.xlsx'
        );
    }
}
