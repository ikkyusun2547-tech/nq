<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ExternalActivityRequest;
use App\Models\User;

class ActivityEvaluationService
{
    /**
     * Institutional graduation criteria: [required activity count, required hours, yearly hour targets by year 1-4].
     */
    private const CRITERIA = [
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
     * Summarize a student's activity-hour progress against SRRU's
     * graduation clearance criteria.
     *
     * @return array{
     *     total_activities: int, required_activities: int,
     *     total_hours: int, required_hours: int,
     *     current_year: int|null, yearly_target_hours: int|null,
     *     category_hours: array<string,int>, is_cleared: bool,
     * }
     */
    public function summarize(User $user): array
    {
        $criteria = self::CRITERIA[$user->program_type] ?? self::CRITERIA['normal'];

        // 'practice' (กิจกรรมซ้อม/เตรียมงาน) credits hours but isn't a real
        // university activity yet, so it's excluded from the 25-activity
        // graduation count while still contributing to total_hours below.
        $totalActivities = Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('attendances.user_id', $user->id)
            ->where('attendances.status', 'auto_approved')
            ->where('activities.activity_type', '!=', 'practice')
            ->count();

        $creditedHoursFromActivities = (int) Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('attendances.user_id', $user->id)
            ->where('attendances.status', 'auto_approved')
            ->selectRaw('COALESCE(SUM(COALESCE(attendances.credited_hours, activities.credit_hours)), 0) as total')
            ->value('total');

        $externalHours = (int) ExternalActivityRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->selectRaw('COALESCE(SUM(COALESCE(hours_approved, hours_requested)), 0) as total')
            ->value('total');

        $totalHours = $creditedHoursFromActivities + $externalHours;

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
            'is_cleared' => $totalActivities >= $criteria['required_activities']
                && $totalHours >= $criteria['required_hours'],
        ];
    }

    /**
     * Final-year students who have fully cleared the graduation activity
     * criteria, for the registrar clearance report. Uses grouped bulk
     * queries (not one query per student) so it stays fast at thousands
     * of students.
     *
     * @return \Illuminate\Support\Collection<int, array{user: User, total_activities: int, total_hours: int}>
     */
    public function clearedGraduatingStudents(int $year = 4): \Illuminate\Support\Collection
    {
        $students = User::with(['faculty', 'major'])
            ->where('role', 'student')
            ->whereNotNull('enrollment_year')
            ->get()
            ->filter(fn (User $u) => $u->current_year === $year)
            ->values();

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

        return $students
            ->map(function (User $user) use ($activityStats, $externalHours) {
                $criteria = self::CRITERIA[$user->program_type] ?? self::CRITERIA['normal'];
                $stat = $activityStats->get($user->id);

                $totalActivities = (int) ($stat->activity_count ?? 0);
                $totalHours = (int) ($stat->activity_hours ?? 0) + (int) ($externalHours[$user->id] ?? 0);

                return [
                    'user' => $user,
                    'total_activities' => $totalActivities,
                    'total_hours' => $totalHours,
                    'is_cleared' => $totalActivities >= $criteria['required_activities']
                        && $totalHours >= $criteria['required_hours'],
                ];
            })
            ->filter(fn (array $row) => $row['is_cleared'])
            ->values();
    }

    /**
     * @return array<string,int> hours accumulated per one of the 5 activity categories.
     */
    private function categoryBreakdown(User $user): array
    {
        $breakdown = array_fill_keys(self::CATEGORIES, 0);

        Attendance::query()
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('attendances.user_id', $user->id)
            ->where('attendances.status', 'auto_approved')
            ->selectRaw('activities.activity_category as category, sum(COALESCE(attendances.credited_hours, activities.credit_hours)) as hours')
            ->groupBy('activities.activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        ExternalActivityRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->selectRaw('activity_category as category, sum(COALESCE(hours_approved, hours_requested)) as hours')
            ->groupBy('activity_category')
            ->pluck('hours', 'category')
            ->each(function ($hours, $category) use (&$breakdown) {
                $breakdown[$category] += (int) $hours;
            });

        return $breakdown;
    }
}
