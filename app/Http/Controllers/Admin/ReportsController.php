<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Just the hub page linking out to each report — every report's own data
 * and export logic lives in its own controller (ClearanceReportController,
 * AtRiskStudentsReportController, ParticipationReportController,
 * CategoryReportController, RequestStatsReportController,
 * ActivityParticipationReportController).
 */
class ReportsController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }
}
