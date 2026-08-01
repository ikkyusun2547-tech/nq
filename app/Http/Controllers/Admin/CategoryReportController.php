<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityEvaluationService;

class CategoryReportController extends Controller
{
    public function index(ActivityEvaluationService $evaluator)
    {
        $categoryHours = $evaluator->universityCategoryBreakdown();

        return view('admin.reports.category', compact('categoryHours'));
    }
}
