<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityEvaluationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The inverse of ClearanceReportController — final-year students who
 * haven't cleared yet, for advisors to follow up with before graduation
 * rather than finding out too late. See
 * ActivityEvaluationService::notClearedGraduatingStudents().
 */
class AtRiskStudentsReportController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * ?sort= value => key on each row's array to sort by. Whitelisted the
     * same way the DB-column pages are — just against array keys instead
     * of columns. A method rather than a const: PHP doesn't allow closures
     * inside a class constant's value.
     */
    private static function sortable(): array
    {
        return [
            'name' => fn (array $row) => $row['user']->name_thai ?? $row['user']->name,
            'total_activities' => fn (array $row) => $row['total_activities'],
            'total_hours' => fn (array $row) => $row['total_hours'],
            'hours_remaining' => fn (array $row) => $row['hours_remaining'],
        ];
    }

    public function index(Request $request, ActivityEvaluationService $evaluator)
    {
        $year = (int) $request->input('year', 4);
        $year = in_array($year, [1, 2, 3, 4], true) ? $year : 4;
        $sortField = $request->input('sort');
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        // Already sorted closest-to-clearing-first (hours_remaining asc) by
        // the service — an explicit ?sort= overrides that with sortBy() on
        // the same already-computed Collection; forPage() then slices
        // whichever order is in effect without disturbing it.
        $allStudents = $evaluator->notClearedGraduatingStudents($year);

        if ($sortKey = self::sortable()[$sortField] ?? null) {
            $allStudents = $sortDir === 'desc'
                ? $allStudents->sortByDesc($sortKey)->values()
                : $allStudents->sortBy($sortKey)->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $students = new LengthAwarePaginator(
            $allStudents->forPage($page, self::PER_PAGE)->values(),
            $allStudents->count(),
            self::PER_PAGE,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('admin.reports.at-risk', compact('students', 'year'));
    }

    public function exportPdf(Request $request, ActivityEvaluationService $evaluator)
    {
        $year = (int) $request->input('year', 4);
        $students = $evaluator->notClearedGraduatingStudents($year);

        $pdf = Pdf::loadView('reports.at-risk-pdf', [
            'students' => $students,
            'year' => $year,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download("at-risk-report-year-{$year}-".now()->format('Ymd').'.pdf');
    }
}
