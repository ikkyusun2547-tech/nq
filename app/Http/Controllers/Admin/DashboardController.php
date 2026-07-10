<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\ExternalActivityRequest;
use App\Models\Faculty;
use App\Models\User;
use App\Services\ActivityEvaluationService;

class DashboardController extends Controller
{
    private const CATEGORIES = ['culture', 'academic', 'sports', 'volunteer', 'ethics'];

    public function __construct(private ActivityEvaluationService $evaluations)
    {
    }

    public function index()
    {
        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'open_activities' => Activity::whereIn('status', ['open', 'ongoing'])->count(),
            'total_activities' => Activity::count(),
            'checkins_this_month' => Attendance::whereBetween('checkin_time', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'pending_external_requests' => ExternalActivityRequest::where('status', 'pending')->count(),
            'flagged_attendances' => Attendance::where('status', 'flagged')->count(),
            'graduating_cleared' => $this->evaluations->clearedGraduatingStudents(4)->count(),
        ];

        $categoryHours = $this->categoryHoursBreakdown();

        $monthlyTrend = $this->monthlyCheckinTrend();

        $facultyParticipation = Attendance::query()
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->join('faculties', 'faculties.id', '=', 'users.faculty_id')
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
        ]);
    }

    /**
     * @return array<string,int> hours accumulated per activity category, system-wide.
     */
    private function categoryHoursBreakdown(): array
    {
        $breakdown = array_fill_keys(self::CATEGORIES, 0);

        Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('attendances.status', 'auto_approved')
            ->selectRaw('activities.activity_category as category, sum(COALESCE(attendances.credited_hours, activities.credit_hours)) as hours')
            ->groupBy('activities.activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        ExternalActivityRequest::where('status', 'approved')
            ->selectRaw('activity_category as category, sum(COALESCE(hours_approved, hours_requested)) as hours')
            ->groupBy('activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        return $breakdown;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{label: string, count: int}> last 6 months, oldest first.
     */
    private function monthlyCheckinTrend(): \Illuminate\Support\Collection
    {
        $start = now()->subMonths(5)->startOfMonth();

        $counts = Attendance::query()
            ->where('checkin_time', '>=', $start)
            ->selectRaw("DATE_FORMAT(checkin_time, '%Y-%m') as ym, count(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return collect(range(0, 5))->map(function (int $i) use ($counts, $start) {
            $month = $start->copy()->addMonths($i);

            return [
                'label' => $month->translatedFormat('M'),
                'count' => (int) ($counts[$month->format('Y-m')] ?? 0),
            ];
        });
    }
}
