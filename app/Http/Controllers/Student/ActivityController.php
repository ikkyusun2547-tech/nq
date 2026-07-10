<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Faculty;
use App\Models\LateCheckInRequest;
use App\Services\AcademicYearCalculator;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Status buckets a student would think in terms of, rather than the
     * raw admin-facing enum (which also has draft/cancelled — the former
     * doubles as "not yet open" here, the latter is never shown to students).
     */
    private const STATUS_GROUPS = [
        'open' => ['open', 'ongoing', 'full'],
        'upcoming' => ['draft'],
        'ended' => ['closed'],
    ];

    /**
     * Browsable feed of activities the student is eligible for, so the
     * banner/description entered by admin actually gets seen by someone
     * before the QR-scan check-in step.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $statusGroup = $request->input('status_group', 'open');
        $statusGroup = array_key_exists($statusGroup, self::STATUS_GROUPS) ? $statusGroup : 'open';

        $academicYears = Activity::query()
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        // Same "default to current year, but respect an explicit 'all years'
        // choice" rule as the admin activity list. Cast to string because
        // ConvertEmptyStringsToNull turns that empty submission into null
        // before it reaches here.
        $academicYear = $request->has('academic_year')
            ? (string) $request->input('academic_year')
            : (string) AcademicYearCalculator::forDate(now());

        $faculties = Faculty::orderBy('name_th')->get();

        $baseQuery = fn () => Activity::query()
            ->when($request->filled('activity_level'), fn ($query) => $query->where('activity_level', $request->input('activity_level')))
            ->when($academicYear !== '', fn ($query) => $query->where('academic_year', $academicYear))
            ->when($request->filled('faculty_id'), function ($query) use ($request) {
                $facultyId = $request->input('faculty_id');

                $query->where(function ($q) use ($facultyId) {
                    $q->whereDoesntHave('restrictions')
                        ->orWhereHas('restrictions', fn ($r) => $r->where('faculty_id', $facultyId));
                });
            })
            ->withCount('attendances');

        if ($statusGroup === 'open') {
            // The main feed: every non-cancelled activity, but ranked open
            // first (soonest first), then upcoming drafts (soonest first),
            // then ended ones (most recently ended first) — rather than the
            // hard status filter the other two tabs use.
            $activities = collect(['open', 'upcoming', 'ended'])
                ->flatMap(function (string $group) use ($baseQuery) {
                    return $baseQuery()
                        ->whereIn('status', self::STATUS_GROUPS[$group])
                        ->orderBy('start_at', $group === 'ended' ? 'desc' : 'asc')
                        ->get();
                })
                ->filter(fn (Activity $activity) => $activity->isEligibleFor($user))
                ->values();
        } else {
            $activities = $baseQuery()
                ->whereIn('status', self::STATUS_GROUPS[$statusGroup])
                ->orderBy('start_at', $statusGroup === 'ended' ? 'desc' : 'asc')
                ->get()
                ->filter(fn (Activity $activity) => $activity->isEligibleFor($user))
                ->values();
        }

        $checkedInActivityIds = $user->attendances()
            ->whereIn('activity_id', $activities->pluck('id'))
            ->pluck('activity_id');

        $lateCheckInStatuses = LateCheckInRequest::where('user_id', $user->id)
            ->whereIn('activity_id', $activities->pluck('id'))
            ->pluck('status', 'activity_id');

        return view('student.activities.index', compact('activities', 'checkedInActivityIds', 'lateCheckInStatuses', 'academicYears', 'academicYear', 'faculties', 'statusGroup'));
    }
}
