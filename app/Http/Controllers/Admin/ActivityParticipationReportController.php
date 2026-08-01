<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ActivityParticipationExport;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class ActivityParticipationReportController extends Controller
{
    private const PER_PAGE = 20;

    /** ?sort= value => real column/alias to order by. Whitelisted so the query string can never inject an arbitrary expression into orderBy(). */
    private const SORTABLE = [
        'title' => 'title',
        'start_at' => 'start_at',
        'attendances_count' => 'attendances_count',
    ];

    public function index(Request $request)
    {
        $sortField = $request->input('sort');
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        // participation_pct/eligible_count only exist once eligibility is
        // computed per activity below, so neither can be an ->orderBy() on
        // the DB-paginated query like the other columns — sorting by either
        // means computing eligibility for every activity up front, sorting
        // that, then slicing into a paginator (same "collection, not query,
        // pagination" pattern as the clearance/at-risk reports), instead of
        // the normal paginate-then-compute-only-this-page's-20 flow below.
        if (in_array($sortField, ['participation_pct', 'eligible_count'], true)) {
            $all = Activity::query()->withCount('attendances')->get()->map(function (Activity $activity) {
                $eligible = $activity->eligibleStudentsCount();
                $activity->eligible_count = $eligible;
                $activity->participation_pct = $eligible > 0
                    ? round($activity->attendances_count / $eligible * 100, 1)
                    : null;

                return $activity;
            });

            $all = $sortDir === 'desc'
                ? $all->sortByDesc($sortField)->values()
                : $all->sortBy($sortField)->values();

            $page = LengthAwarePaginator::resolveCurrentPage();
            $activities = new LengthAwarePaginator(
                $all->forPage($page, self::PER_PAGE)->values(),
                $all->count(),
                self::PER_PAGE,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
            );

            return view('admin.reports.activity-participation', compact('activities'));
        }

        $sortColumn = self::SORTABLE[$sortField] ?? null;

        $activities = Activity::query()
            ->withCount('attendances')
            ->when(
                $sortColumn,
                fn ($query) => $query->orderBy($sortColumn, $sortDir),
                fn ($query) => $query->orderByDesc('start_at')
            )
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // eligibleStudentsCount() is one query per activity — fine bounded
        // to a page of 20, would not be at the full unpaginated table (see
        // ActivityParticipationExport, which accepts that cost once for a
        // background-feeling Excel download instead of a page load).
        $activities->getCollection()->transform(function (Activity $activity) {
            $eligible = $activity->eligibleStudentsCount();
            $activity->eligible_count = $eligible;
            $activity->participation_pct = $eligible > 0
                ? round($activity->attendances_count / $eligible * 100, 1)
                : null;

            return $activity;
        });

        return view('admin.reports.activity-participation', compact('activities'));
    }

    public function exportExcel()
    {
        return Excel::download(
            new ActivityParticipationExport,
            'activity-participation-'.now()->format('Ymd').'.xlsx'
        );
    }
}
