<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Faculty;
use App\Models\LateCheckInRequest;
use App\Services\AcademicYearCalculator;
use App\Services\StudentAttention;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

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

    /** Matches the 3-column card grid — 9 is exactly 3 full rows. */
    private const PER_PAGE = 9;

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

        // Late check-in results live on the "ended" tab — opening it clears the nav dot.
        if ($statusGroup === 'ended') {
            StudentAttention::markSeen($user, 'activities');
        }

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
            ->when($request->filled('activity_category'), fn ($query) => $query->where('activity_category', $request->input('activity_category')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('organizer_name', 'like', "%{$search}%");
                });
            })
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
            // The main feed: every non-cancelled activity the student is
            // eligible for, ranked by what they should act on first (see
            // feedRank()) — soonest first within each rank, except ended
            // ones at the bottom, which go most recently ended first.
            $attendedIds = $user->attendances()->pluck('activity_id')->flip();

            $activities = $baseQuery()
                ->where('status', '!=', 'cancelled')
                ->with('restrictions')
                ->get()
                ->filter(fn (Activity $activity) => $activity->isEligibleFor($user))
                ->sortBy(function (Activity $activity) use ($attendedIds) {
                    $rank = $this->feedRank($activity, $attendedIds->has($activity->id));
                    $when = $rank === 7
                        ? 99991231235959 - (int) $activity->end_at->format('YmdHis')
                        : (int) $activity->start_at->format('YmdHis');

                    return sprintf('%d-%014d', $rank, $when);
                })
                ->values();
        } else {
            $activities = $baseQuery()
                ->whereIn('status', self::STATUS_GROUPS[$statusGroup])
                ->orderBy('start_at', $statusGroup === 'ended' ? 'desc' : 'asc')
                ->get()
                ->filter(fn (Activity $activity) => $activity->isEligibleFor($user))
                ->values();
        }

        // Eligibility is filtered in PHP above (isEligibleFor() isn't a SQL
        // condition), so this can't be a normal ->paginate() — the query
        // would slice before filtering. Paginating the already-filtered
        // Collection instead keeps the eligibility logic untouched.
        $page = LengthAwarePaginator::resolveCurrentPage();
        $activities = new LengthAwarePaginator(
            $activities->forPage($page, self::PER_PAGE)->values(),
            $activities->count(),
            self::PER_PAGE,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $checkedInActivityIds = $user->attendances()
            ->whereIn('activity_id', $activities->pluck('id'))
            ->pluck('activity_id');

        // Latest late check-in request status per activity on this page, so a
        // missed activity's card can offer "ขอเช็คชื่อย้อนหลัง" (or show it's pending).
        $lateRequestStatuses = LateCheckInRequest::where('user_id', $user->id)
            ->whereIn('activity_id', $activities->pluck('id'))
            ->orderBy('id')
            ->pluck('status', 'activity_id');

        return view('student.activities.index', compact('activities', 'checkedInActivityIds', 'lateRequestStatuses', 'academicYears', 'academicYear', 'faculties', 'statusGroup'));
    }

    /**
     * Main-feed priority, lowest first:
     *  1–2. open right now (ongoing ahead of open) — can check in today
     *  3.   not open yet but aimed at this student's faculty/major/year
     *  4.   not open yet, open to everyone
     *  5.   full — can't join, but still worth seeing
     *  6.   already checked in — nothing left to do
     *  7.   ended (closed, or past its end time but not auto-closed yet)
     */
    private function feedRank(Activity $activity, bool $attended): int
    {
        return match (true) {
            $activity->status === 'closed' || $activity->end_at->isPast() => 7,
            $attended => 6,
            $activity->status === 'ongoing' => 1,
            $activity->status === 'open' => 2,
            $activity->status === 'full' => 5,
            $activity->restrictions->isNotEmpty() => 3,
            default => 4,
        };
    }

    /**
     * Full detail page a card in the browsable feed links through to, since
     * the card itself only surfaces a handful of at-a-glance fields.
     */
    public function show(Activity $activity, Request $request)
    {
        $user = $request->user();

        abort_unless($activity->isEligibleFor($user), 404);

        $checkedIn = $user->attendances()->where('activity_id', $activity->id)->exists();
        $lateCheckInStatus = LateCheckInRequest::where('user_id', $user->id)
            ->where('activity_id', $activity->id)
            ->value('status');

        return view('student.activities.show', compact('activity', 'checkedIn', 'lateCheckInStatus'));
    }
}
