<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\CreditTransferRequest;
use App\Models\ExternalActivityRequest;
use App\Models\Faculty;
use App\Models\User;
use App\Services\AcademicYearCalculator;
use App\Services\ActivityEvaluationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const CATEGORIES = ['culture', 'academic', 'sports', 'volunteer', 'ethics'];

    public function __construct(private ActivityEvaluationService $evaluations)
    {
    }

    public function index(Request $request)
    {
        // Same "default to current year, explicit 'all years' respected" rule
        // as the activity lists. Only activity/hours-based figures below are
        // scoped by it — headcounts and admin to-do queues stay system-wide.
        // Cast to string because ConvertEmptyStringsToNull turns that empty
        // submission into null before it reaches here.
        $academicYear = $request->has('academic_year')
            ? (string) $request->input('academic_year')
            : (string) AcademicYearCalculator::forDate(now());

        $academicYears = Activity::query()
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'open_activities' => Activity::whereIn('status', ['open', 'ongoing'])
                ->when($academicYear !== '', fn ($query) => $query->where('academic_year', $academicYear))
                ->count(),
            'total_activities' => Activity::when($academicYear !== '', fn ($query) => $query->where('academic_year', $academicYear))
                ->count(),
            'checkins_this_month' => Attendance::whereBetween('checkin_time', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'pending_external_requests' => ExternalActivityRequest::where('status', 'pending')->count(),
            'pending_credit_transfers' => CreditTransferRequest::where('status', 'pending')->count(),
            'flagged_attendances' => Attendance::where('status', 'flagged')->count(),
            'graduating_cleared' => $this->evaluations->clearedGraduatingStudents(4)->count(),
        ];

        $categoryHours = $this->categoryHoursBreakdown($academicYear);

        $monthlyTrend = $this->monthlyCheckinTrend($academicYear);

        $facultyParticipation = Attendance::query()
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->join('faculties', 'faculties.id', '=', 'users.faculty_id')
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->when($academicYear !== '', fn ($query) => $query->where('activities.academic_year', $academicYear))
            ->selectRaw('faculties.name_th as faculty, count(*) as total')
            ->groupBy('faculties.id', 'faculties.name_th')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $upcomingActivities = Activity::whereIn('status', ['open', 'ongoing', 'draft'])
            ->withCount('attendances')
            ->orderBy('start_at')
            ->limit(5)
            ->get()
            ->map(function (Activity $activity) {
                $activity->required_count = $activity->eligibleStudentsCount();

                return $activity;
            });

        $pendingRequests = ExternalActivityRequest::with('user')
            ->where('status', 'pending')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'categoryHours' => $categoryHours,
            'monthlyTrend' => $monthlyTrend,
            'facultyParticipation' => $facultyParticipation,
            'upcomingActivities' => $upcomingActivities,
            'pendingRequests' => $pendingRequests,
            'academicYear' => $academicYear,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * @return array<string,int> hours accumulated per activity category, optionally scoped to one academic year.
     */
    private function categoryHoursBreakdown(string $academicYear): array
    {
        $breakdown = array_fill_keys(self::CATEGORIES, 0);

        Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('attendances.status', 'auto_approved')
            ->when($academicYear !== '', fn ($query) => $query->where('activities.academic_year', $academicYear))
            ->selectRaw('activities.activity_category as category, sum(COALESCE(attendances.credited_hours, activities.credit_hours)) as hours')
            ->groupBy('activities.activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        ExternalActivityRequest::where('status', 'approved')
            ->when($academicYear !== '', function ($query) use ($academicYear) {
                [$start, $end] = AcademicYearCalculator::rangeFor((int) $academicYear);
                $query->whereBetween('activity_date', [$start, $end]);
            })
            ->selectRaw('activity_category as category, sum(COALESCE(hours_approved, hours_requested)) as hours')
            ->groupBy('activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        CreditTransferRequest::where('status', 'approved')
            ->when($academicYear !== '', fn ($query) => $query->where('academic_year', $academicYear))
            ->selectRaw('activity_category as category, sum(COALESCE(hours_approved, hours_requested)) as hours')
            ->groupBy('activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        return $breakdown;
    }

    /**
     * Check-in counts per month. With no academic year selected this is a
     * rolling window of the last 6 calendar months (oldest first); with one
     * selected it's all 12 months of that academic year, Jun through May.
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, count: int}>
     */
    private function monthlyCheckinTrend(string $academicYear): \Illuminate\Support\Collection
    {
        if ($academicYear === '') {
            $start = now()->subMonths(5)->startOfMonth();
            $end = now()->endOfMonth();
            $monthCount = 6;
        } else {
            [$rangeStart, $rangeEnd] = AcademicYearCalculator::rangeFor((int) $academicYear);
            $start = $rangeStart->copy()->startOfMonth();
            $end = $rangeEnd->copy()->endOfMonth();
            $monthCount = 12;
        }

        $counts = Attendance::query()
            ->whereBetween('checkin_time', [$start, $end])
            ->selectRaw("DATE_FORMAT(checkin_time, '%Y-%m') as ym, count(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return collect(range(0, $monthCount - 1))->map(function (int $i) use ($counts, $start) {
            $month = $start->copy()->addMonths($i);

            return [
                'label' => $month->translatedFormat('M'),
                'count' => (int) ($counts[$month->format('Y-m')] ?? 0),
            ];
        });
    }
}
