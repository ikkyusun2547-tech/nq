<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityEvaluationService;

class RequestStatsReportController extends Controller
{
    public function index(ActivityEvaluationService $evaluator)
    {
        $stats = $evaluator->requestStats();

        return view('admin.reports.request-stats', compact('stats'));
    }
}
