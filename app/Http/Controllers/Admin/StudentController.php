<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\LateCheckInRequest;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentController extends Controller
{
    /** field (from ?sort=) => real column to order by. Whitelisted so the query string can never inject an arbitrary column/expression into orderBy(). */
    private const SORTABLE = [
        'student_id' => 'student_id',
        'name' => 'name_thai',
        'year_level' => 'year_level',
        'program_type' => 'program_type',
    ];

    private const PER_PAGE = 20;

    public function index(Request $request, ActivityEvaluationService $evaluationService)
    {
        $sortField = $request->input('sort');
        $sortColumn = self::SORTABLE[$sortField] ?? null;
        $sortDir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        // Default view is the current student body — graduated students are
        // kept for historical records but shouldn't clutter the day-to-day
        // roster unless an admin explicitly asks to see them.
        $enrollmentStatus = in_array($request->input('enrollment_status'), ['graduated', 'all'], true)
            ? $request->input('enrollment_status')
            : 'enrolled';

        $baseQuery = User::query()
            ->where('role', 'student')
            ->with(['faculty', 'major'])
            ->when($enrollmentStatus === 'enrolled', fn ($query) => $query->whereNull('graduated_at'))
            ->when($enrollmentStatus === 'graduated', fn ($query) => $query->whereNotNull('graduated_at'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($q) use ($search) {
                    $q->where('name_thai', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('faculty_id'), fn ($query) => $query->where('faculty_id', $request->input('faculty_id')))
            ->when($request->filled('major_id'), fn ($query) => $query->where('major_id', $request->input('major_id')))
            ->when($request->filled('year_level'), fn ($query) => $query->where('year_level', $request->input('year_level')));

        if ($sortField === 'hours') {
            // total_hours isn't a database column — it's aggregated across
            // three separate request tables in ActivityEvaluationService —
            // so it can't be sorted with a plain orderBy() before pagination
            // like the whitelisted columns above. Load every student that
            // matches the filters (a university's active roster, not an
            // unbounded table), compute all their progress in one batch,
            // sort that in PHP, then hand-slice the page instead of letting
            // the DB paginate rows it can't order by this value.
            $allMatching = (clone $baseQuery)->orderBy('student_id')->get();
            $progressAll = $evaluationService->bulkProgress($allMatching)->keyBy(fn (array $row) => $row['user']->id);

            $sorted = $allMatching->sortBy(
                fn (User $user) => $progressAll[$user->id]['total_hours'] ?? 0,
                SORT_REGULAR,
                $sortDir === 'desc'
            )->values();

            $page = max(1, (int) $request->input('page', 1));
            $students = new LengthAwarePaginator(
                $sorted->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values(),
                $sorted->count(),
                self::PER_PAGE,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $progressByStudent = $progressAll;
        } else {
            $students = (clone $baseQuery)
                ->when(
                    $sortColumn,
                    fn ($query) => $query->orderBy($sortColumn, $sortDir)->orderBy('student_id'),
                    fn ($query) => $query->orderBy('student_id')
                )
                ->paginate(self::PER_PAGE)
                ->withQueryString();

            // Hours/activity progress for just this page's 20 rows — bulkProgress()
            // runs a handful of grouped queries for the whole batch at once rather
            // than summarize() per row, which would otherwise cost 7-8 queries ×
            // 20 students on every single page load of this listing.
            $progressByStudent = $evaluationService->bulkProgress($students->getCollection())->keyBy(fn (array $row) => $row['user']->id);
        }

        $faculties = Faculty::with(['majors' => fn ($query) => $query->orderBy('name_th')])->orderBy('name_th')->get();

        $bannedCount = User::where('role', 'student')->whereNull('graduated_at')->where('account_status', 'banned')->count();

        return view('admin.students.index', compact('students', 'faculties', 'bannedCount', 'enrollmentStatus', 'progressByStudent'));
    }

    public function show(User $student, ActivityEvaluationService $evaluationService)
    {
        abort_unless($student->role === 'student', 404);

        $student->load(['faculty', 'major']);

        $summary = $evaluationService->summarize($student);

        $attendances = $student->attendances()
            ->with('activity')
            ->latest('checkin_time')
            ->take(10)
            ->get();

        $externalRequests = $student->externalActivityRequests()
            ->latest('activity_date')
            ->take(10)
            ->get();

        $lateCheckIns = LateCheckInRequest::where('user_id', $student->id)
            ->with('activity')
            ->latest('created_at')
            ->take(10)
            ->get();

        $creditTransfers = $student->creditTransferRequests()
            ->latest('created_at')
            ->take(10)
            ->get();

        return view('admin.students.show', compact(
            'student', 'summary', 'attendances', 'externalRequests', 'lateCheckIns', 'creditTransfers'
        ));
    }
}
