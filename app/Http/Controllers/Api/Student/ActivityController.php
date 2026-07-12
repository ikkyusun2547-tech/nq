<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\FacultyResource;
use App\Models\Activity;
use App\Models\Faculty;
use App\Models\LateCheckInRequest;
use App\Services\AcademicYearCalculator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityController extends Controller
{
    private const STATUS_GROUPS = [
        'open' => ['open', 'ongoing', 'full'],
        'upcoming' => ['draft'],
        'ended' => ['closed'],
    ];

    private const PER_PAGE = 9;

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

        $lateCheckInStatuses = LateCheckInRequest::where('user_id', $user->id)
            ->whereIn('activity_id', $activities->pluck('id'))
            ->pluck('status', 'activity_id');

        return response()->json([
            'data' => ActivityResource::collection($activities->getCollection()),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
            'checked_in_activity_ids' => $checkedInActivityIds,
            'late_checkin_statuses' => $lateCheckInStatuses,
            'faculties' => FacultyResource::collection($faculties),
            'academic_years' => $academicYears,
            'filters' => [
                'status_group' => $statusGroup,
                'academic_year' => $academicYear,
            ],
        ]);
    }
}
