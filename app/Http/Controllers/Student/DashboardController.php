<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ActivityEvaluationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request, ActivityEvaluationService $evaluator)
    {
        $summary = $evaluator->summarize($request->user());

        return view('student.dashboard', compact('summary'));
    }
}
