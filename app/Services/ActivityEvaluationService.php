<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CreditTransferPosition;
use App\Models\CreditTransferRequest;
use App\Models\ExternalActivityRequest;
use App\Models\Faculty;
use App\Models\GraduationCriteria;
use App\Models\LateCheckInRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class ActivityEvaluationService
{
    /**
     * The ultimate fallback when no admin has ever configured a
     * GraduationCriteria row that applies to a student's cohort — so the
     * app has a sane requirement to evaluate against from a fresh install,
     * with nothing to seed. See criteria() below for how a real row (if
     * any exists) takes precedence over this.
     */
    public const DEFAULT_CRITERIA = [
        'normal' => [
            'required_activities' => 25,
            'required_hours' => 100,
            'yearly_targets' => [1 => 40, 2 => 30, 3 => 20, 4 => 10],
        ],
        'special' => [
            'required_activities' => 4,
            'required_hours' => 50,
            'yearly_targets' => [1 => 20, 2 => 15, 3 => 10, 4 => 5],
        ],
    ];

    private const CATEGORIES = ['culture', 'academic', 'sports', 'volunteer', 'ethics'];

    /**
     * The criteria actually in effect for one student's cohort + program.
     * Resolution order:
     *   1. A GraduationCriteria row for this exact (enrollment_year, program_type).
     *   2. The nearest *earlier* configured enrollment_year for the same
     *      program_type — a student's requirement is whatever was published
     *      by the time they enrolled, not something set later for a newer
     *      cohort.
     *   3. DEFAULT_CRITERIA, if nothing has ever been configured for this
     *      program_type at all.
     *
     * @return array{required_activities: int, required_hours: int, yearly_targets: array<int,int>}
     */
    public function criteria(?int $enrollmentYear, string $programType): array
    {
        return $this->resolveCriteria($this->allCriteriaRows(), $enrollmentYear, $programType);
    }

    /**
     * Every configured cohort's criteria, fetched once — the table is
     * bounded by (number of cohorts admins have configured) × 2 program
     * types, nowhere near large enough to need per-lookup queries even
     * across a bulk operation like bulkProgress() below.
     *
     * @return Collection<int, GraduationCriteria>
     */
    private function allCriteriaRows(): Collection
    {
        return GraduationCriteria::all();
    }

    /**
     * @param  Collection<int, GraduationCriteria>  $rows
     * @return array{required_activities: int, required_hours: int, yearly_targets: array<int,int>}
     */
    private function resolveCriteria(Collection $rows, ?int $enrollmentYear, string $programType): array
    {
        $forProgram = $rows->where('program_type', $programType);

        $match = $enrollmentYear !== null
            ? $forProgram->firstWhere('enrollment_year', $enrollmentYear)
            : null;

        if (! $match && $enrollmentYear !== null) {
            $match = $forProgram
                ->where('enrollment_year', '<=', $enrollmentYear)
                ->sortByDesc('enrollment_year')
                ->first();
        }

        if ($match) {
            return [
                'required_activities' => $match->required_activities,
                'required_hours' => $match->required_hours,
                'yearly_targets' => $match->yearly_targets,
            ];
        }

        return self::DEFAULT_CRITERIA[$programType] ?? self::DEFAULT_CRITERIA['normal'];
    }

    /**
     * Summarize a student's activity-hour progress against SRRU's
     * graduation clearance criteria.
     *
     * @return array{
     *     total_activities: int, required_activities: int,
     *     total_hours: int, required_hours: int,
     *     current_year: int|null, yearly_target_hours: int|null,
     *     category_hours: array<string,int>, hours_by_source: array<string,int>,
     *     is_cleared: bool,
     * }
     */
    public function summarize(User $user): array
    {
        // program_type is nullable on the User model (unset until profile
        // setup completes) — 'normal' is the same assumed default the old
        // single-blob criteria() lookup fell back to.
        $criteria = $this->criteria($user->enrollment_year, $user->program_type ?? 'normal');

        // 'practice' (กิจกรรมซ้อม/เตรียมงาน) credits hours but isn't a real
        // university activity yet, so it's excluded from the 25-activity
        // graduation count while still contributing to total_hours below.
        $totalActivities = Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('attendances.user_id', $user->id)
            ->where('attendances.status', 'auto_approved')
            ->where('activities.activity_type', '!=', 'practice')
            ->count();

        // Grouped by checkin_method (realtime/self_report/late_request) rather
        // than a single summed total — the dashboard's "ที่มาของชั่วโมงสะสม"
        // card wants the finer breakdown, and the overall total below is just
        // this collection summed instead of a second, near-identical query.
        $hoursByCheckinMethod = Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('attendances.user_id', $user->id)
            ->where('attendances.status', 'auto_approved')
            ->groupBy('attendances.checkin_method')
            ->selectRaw('attendances.checkin_method, COALESCE(SUM(COALESCE(attendances.credited_hours, activities.credit_hours)), 0) as total')
            ->pluck('total', 'checkin_method');

        $creditedHoursFromActivities = (int) $hoursByCheckinMethod->sum();

        $externalHours = (int) ExternalActivityRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->selectRaw('COALESCE(SUM(COALESCE(hours_approved, hours_requested)), 0) as total')
            ->value('total');

        $creditTransferHours = (int) CreditTransferRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->selectRaw('COALESCE(SUM(COALESCE(hours_approved, hours_requested)), 0) as total')
            ->value('total');

        $totalHours = $creditedHoursFromActivities + $externalHours + $creditTransferHours;

        $categoryHours = $this->categoryBreakdown($user);

        $currentYear = $user->current_year !== null ? min(4, $user->current_year) : null;
        $yearlyTarget = $currentYear ? $criteria['yearly_targets'][$currentYear] : null;

        return [
            'total_activities' => $totalActivities,
            'required_activities' => $criteria['required_activities'],
            'total_hours' => $totalHours,
            'required_hours' => $criteria['required_hours'],
            'current_year' => $currentYear,
            'yearly_target_hours' => $yearlyTarget,
            'category_hours' => $categoryHours,
            // Same figures total_hours is already summed from above —
            // surfaced separately so the dashboard can show where a
            // student's hours actually came from, not just the total.
            'hours_by_source' => [
                'realtime' => (int) ($hoursByCheckinMethod['realtime'] ?? 0),
                'self_report' => (int) ($hoursByCheckinMethod['self_report'] ?? 0),
                'late_request' => (int) ($hoursByCheckinMethod['late_request'] ?? 0),
                'external' => $externalHours,
                'credit_transfer' => $creditTransferHours,
            ],
            'is_cleared' => $totalActivities >= $criteria['required_activities']
                && $totalHours >= $criteria['required_hours'],
        ];
    }

    /**
     * The position label a student currently holds, if any — only counts an
     * approved credit-transfer claim for the *current* academic year, since
     * a position held in a past year isn't a role the student holds
     * anymore. Shared by the web and API student dashboards so both surface
     * the same "ดำรงตำแหน่ง" badge from one query instead of two copies.
     */
    public function currentPositionLabel(User $user): ?string
    {
        $request = CreditTransferRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('academic_year', AcademicYearCalculator::forDate(now()))
            ->first();

        if (! $request) {
            return null;
        }

        return CreditTransferPosition::labelsMap()[$request->position] ?? $request->position;
    }

    /**
     * Bulk-computed progress (total_activities, total_hours, is_cleared) for
     * an arbitrary set of students — the shared engine behind
     * studentsProgress() (one academic year, for the clearance/at-risk
     * reports) and facultyParticipationSummary() (every student, grouped by
     * faculty instead). A handful of grouped queries regardless of how many
     * students are passed in — never one query per student, which is what
     * made facultyParticipationSummary() call summarize() per student used
     * to cost (7-8 queries × every student in the university, on every
     * request).
     *
     * @param  Collection<int, User>  $students
     * @return Collection<int, array{
     *     user: User, total_activities: int, total_hours: int,
     *     required_activities: int, required_hours: int, is_cleared: bool,
     * }>
     */
    public function bulkProgress(Collection $students): Collection
    {
        $studentIds = $students->pluck('id');

        if ($studentIds->isEmpty()) {
            return collect();
        }

        $activityStats = Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->whereIn('attendances.user_id', $studentIds)
            ->where('attendances.status', 'auto_approved')
            ->selectRaw("attendances.user_id as user_id, sum(case when activities.activity_type != 'practice' then 1 else 0 end) as activity_count, sum(COALESCE(attendances.credited_hours, activities.credit_hours)) as activity_hours")
            ->groupBy('attendances.user_id')
            ->get()
            ->keyBy('user_id');

        $externalHours = ExternalActivityRequest::whereIn('user_id', $studentIds)
            ->where('status', 'approved')
            ->selectRaw('user_id, sum(COALESCE(hours_approved, hours_requested)) as hours')
            ->groupBy('user_id')
            ->pluck('hours', 'user_id');

        $creditTransferHours = CreditTransferRequest::whereIn('user_id', $studentIds)
            ->where('status', 'approved')
            ->selectRaw('user_id, sum(COALESCE(hours_approved, hours_requested)) as hours')
            ->groupBy('user_id')
            ->pluck('hours', 'user_id');

        $criteriaRows = $this->allCriteriaRows();

        return $students->map(function (User $user) use ($activityStats, $externalHours, $creditTransferHours, $criteriaRows) {
            $criteria = $this->resolveCriteria($criteriaRows, $user->enrollment_year, $user->program_type ?? 'normal');
            $stat = $activityStats->get($user->id);

            $totalActivities = (int) ($stat->activity_count ?? 0);
            $totalHours = (int) ($stat->activity_hours ?? 0)
                + (int) ($externalHours[$user->id] ?? 0)
                + (int) ($creditTransferHours[$user->id] ?? 0);

            return [
                'user' => $user,
                'total_activities' => $totalActivities,
                'total_hours' => $totalHours,
                'required_activities' => $criteria['required_activities'],
                'required_hours' => $criteria['required_hours'],
                'is_cleared' => $totalActivities >= $criteria['required_activities']
                    && $totalHours >= $criteria['required_hours'],
            ];
        });
    }

    /**
     * Per-student graduation progress for every enrolled student in a given
     * academic year — the shared base for both clearedGraduatingStudents()
     * (registrar handoff) and notClearedGraduatingStudents() (advisor
     * follow-up), so the same bulk queries aren't run twice for what's
     * really one dataset sliced two ways.
     *
     * @return Collection<int, array{
     *     user: User, total_activities: int, total_hours: int,
     *     required_activities: int, required_hours: int, is_cleared: bool,
     * }>
     */
    public function studentsProgress(int $year): Collection
    {
        $students = User::with(['faculty', 'major'])
            ->where('role', 'student')
            ->whereNull('graduated_at')
            ->whereNotNull('enrollment_year')
            ->get()
            ->filter(fn (User $u) => $u->current_year === $year)
            ->values();

        return $this->bulkProgress($students);
    }

    /**
     * Final-year students who have fully cleared the graduation activity
     * criteria, for the registrar clearance report.
     *
     * @return Collection<int, array{user: User, total_activities: int, total_hours: int}>
     */
    public function clearedGraduatingStudents(int $year = 4): Collection
    {
        return $this->studentsProgress($year)
            ->filter(fn (array $row) => $row['is_cleared'])
            ->values();
    }

    /**
     * The inverse of clearedGraduatingStudents() — final-year students who
     * have NOT cleared yet, for advisors to follow up with before
     * graduation. Sorted closest-to-clearing first (least hours still
     * needed) since that's usually the most actionable slice to work
     * through first.
     *
     * @return Collection<int, array{user: User, total_activities: int, total_hours: int, activities_remaining: int, hours_remaining: int}>
     */
    public function notClearedGraduatingStudents(int $year = 4): Collection
    {
        return $this->studentsProgress($year)
            ->filter(fn (array $row) => ! $row['is_cleared'])
            ->map(function (array $row) {
                $row['activities_remaining'] = max(0, $row['required_activities'] - $row['total_activities']);
                $row['hours_remaining'] = max(0, $row['required_hours'] - $row['total_hours']);

                return $row;
            })
            ->sortBy('hours_remaining')
            ->values();
    }

    /**
     * Hours accumulated per one of the 5 activity categories — for a single
     * student when $user is given (the student dashboard's per-person
     * breakdown), or university-wide across every student when omitted
     * (see universityCategoryBreakdown() below, for the planning report:
     * which of the 5 areas is under-served by the activities on offer).
     *
     * @return array<string,int>
     */
    private function categoryBreakdown(?User $user = null): array
    {
        $breakdown = array_fill_keys(self::CATEGORIES, 0);

        Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('attendances.status', 'auto_approved')
            ->when($user, fn ($q) => $q->where('attendances.user_id', $user->id))
            ->selectRaw('activities.activity_category as category, sum(COALESCE(attendances.credited_hours, activities.credit_hours)) as hours')
            ->groupBy('activities.activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        ExternalActivityRequest::query()
            ->where('status', 'approved')
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->selectRaw('activity_category as category, sum(COALESCE(hours_approved, hours_requested)) as hours')
            ->groupBy('activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        // Position credits no longer carry a category (they count toward the
        // totals only); older rows that were given one still show up here.
        CreditTransferRequest::query()
            ->where('status', 'approved')
            ->whereNotNull('activity_category')
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->selectRaw('activity_category as category, sum(COALESCE(hours_approved, hours_requested)) as hours')
            ->groupBy('activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        return $breakdown;
    }

    /**
     * @return array<string,int> hours accumulated per category, across every student.
     */
    public function universityCategoryBreakdown(): array
    {
        return $this->categoryBreakdown();
    }

    /**
     * Every faculty's participation at a glance — student count, how many
     * have cleared the graduation criteria, and average hours/activities
     * per student. Shared by the on-screen report and its Excel export so
     * the two can never drift out of sync with each other.
     *
     * Uses bulkProgress() (a handful of grouped queries) rather than calling
     * summarize() once per student — the previous version cost 7-8 queries
     * per student, which meant every request to this report re-ran roughly
     * (7-8 × total student count) queries. That's fine at a few dozen
     * students and genuinely bad at a few thousand.
     *
     * Excludes graduated students, same as studentsProgress() — this reads
     * as "how is each faculty's current student body doing", not a
     * historical all-time clearance rate.
     *
     * @return Collection<int, array{
     *     faculty: Faculty, student_count: int, cleared_count: int,
     *     cleared_pct: float, avg_hours: float, avg_activities: float,
     * }>
     */
    public function facultyParticipationSummary(): Collection
    {
        $students = User::where('role', 'student')
            ->whereNull('graduated_at')
            ->get(['id', 'faculty_id', 'program_type', 'enrollment_year']);
        $progressByFaculty = $this->bulkProgress($students)->groupBy(fn (array $row) => $row['user']->faculty_id);

        return Faculty::orderBy('name_th')->get()->map(function (Faculty $faculty) use ($progressByFaculty) {
            $rows = $progressByFaculty->get($faculty->id, collect());
            $studentCount = $rows->count();
            $clearedCount = $rows->where('is_cleared', true)->count();

            return [
                'faculty' => $faculty,
                'student_count' => $studentCount,
                'cleared_count' => $clearedCount,
                'cleared_pct' => $studentCount > 0 ? round($clearedCount / $studentCount * 100, 1) : 0.0,
                'avg_hours' => $studentCount > 0 ? round($rows->avg('total_hours'), 1) : 0.0,
                'avg_activities' => $studentCount > 0 ? round($rows->avg('total_activities'), 1) : 0.0,
            ];
        });
    }

    /**
     * Volume and turnaround for the three admin-reviewed request types
     * (external activity, credit transfer, late check-in) — how many of
     * each are submitted, what fraction get approved, and how long a
     * decided request typically waits for review.
     *
     * @return array<string, array{
     *     total: int, pending: int, approved: int, rejected: int,
     *     approval_rate: float|null, avg_turnaround_hours: float|null,
     * }>
     */
    public function requestStats(): array
    {
        $models = [
            'external' => ExternalActivityRequest::class,
            'credit_transfer' => CreditTransferRequest::class,
            'late_checkin' => LateCheckInRequest::class,
        ];

        $stats = [];

        foreach ($models as $key => $modelClass) {
            $counts = $modelClass::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $approved = (int) ($counts['approved'] ?? 0);
            $rejected = (int) ($counts['rejected'] ?? 0);
            $decided = $approved + $rejected;

            // Averaged in PHP rather than a DB-side TIMESTAMPDIFF/JULIANDAY
            // call — those functions aren't portable across the MySQL the
            // app runs on and the SQLite the test suite runs on (see
            // phpunit.xml), and reviewed-request volume is nowhere near
            // large enough for this to matter performance-wise.
            $reviewed = $modelClass::query()
                ->whereNotNull('reviewed_at')
                ->get(['created_at', 'reviewed_at']);

            $avgTurnaroundHours = $reviewed->isNotEmpty()
                ? $reviewed->avg(fn ($row) => $row->created_at->diffInMinutes($row->reviewed_at) / 60)
                : null;

            $stats[$key] = [
                'total' => (int) $counts->sum(),
                'pending' => (int) ($counts['pending'] ?? 0),
                'approved' => $approved,
                'rejected' => $rejected,
                'approval_rate' => $decided > 0 ? round($approved / $decided * 100, 1) : null,
                'avg_turnaround_hours' => $avgTurnaroundHours !== null ? round($avgTurnaroundHours, 1) : null,
            ];
        }

        return $stats;
    }
}
