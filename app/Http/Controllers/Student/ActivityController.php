<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Faculty;
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

        $faculties = Faculty::orderBy('name_th')->get();

        $activities = Activity::query()
            ->whereIn('status', self::STATUS_GROUPS[$statusGroup])
            ->when($request->filled('activity_level'), fn ($query) => $query->where('activity_level', $request->input('activity_level')))
            ->when($request->filled('academic_year'), fn ($query) => $query->where('academic_year', $request->input('academic_year')))
            ->when($request->filled('faculty_id'), function ($query) use ($request) {
                $facultyId = $request->input('faculty_id');

                $query->where(function ($q) use ($facultyId) {
                    $q->whereDoesntHave('restrictions')
                        ->orWhereHas('restrictions', fn ($r) => $r->where('faculty_id', $facultyId));
                });
            })
            ->withCount('attendances')
            ->orderBy('start_at', $statusGroup === 'ended' ? 'desc' : 'asc')
            ->get()
            ->filter(fn (Activity $activity) => $activity->isEligibleFor($user))
            ->values();

        $checkedInActivityIds = $user->attendances()
            ->whereIn('activity_id', $activities->pluck('id'))
            ->pluck('activity_id');

        return view('student.activities.index', compact('activities', 'checkedInActivityIds', 'academicYears', 'faculties', 'statusGroup'));
    }
}
