<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Models\Activity;
use App\Models\ActivityRestriction;
use App\Models\Faculty;
use App\Notifications\ActivityCreated;
use App\Notifications\ActivityMissed;
use App\Notifications\ActivityUpdated;
use App\Services\AcademicYearCalculator;
use App\Services\ActivityCodeGenerator;
use App\Services\SafeNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    public function __construct(protected ActivityCodeGenerator $activityCodes)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // On a fresh visit (no academic_year in the query string at all) default
        // to the current academic year so the list isn't cluttered with every
        // past year; an explicit "-- ทุกปีการศึกษา --" selection posts an empty
        // value and is respected as "show all" rather than re-defaulted. Cast
        // to string because ConvertEmptyStringsToNull turns that empty
        // submission into null before it reaches here.
        $academicYear = $request->has('academic_year')
            ? (string) $request->input('academic_year')
            : (string) AcademicYearCalculator::forDate(now());

        $activities = Activity::withCount([
            'attendances',
            'attendances as flagged_count' => fn ($query) => $query->where('status', 'flagged'),
        ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->string('search').'%');
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($academicYear !== '', fn ($query) => $query->where('academic_year', $academicYear))
            ->when($request->filled('semester'), fn ($query) => $query->where('semester', $request->input('semester')))
            ->latest('start_at')
            ->paginate(20)
            ->withQueryString();

        $academicYears = Activity::query()
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        // Scoped only by academic year (not search/status/semester) so the
        // status chips always reflect "everything in this year" regardless
        // of what the table below is currently filtered to — that's what
        // makes them useful as one-click filter shortcuts.
        $statusCounts = Activity::query()
            ->when($academicYear !== '', fn ($query) => $query->where('academic_year', $academicYear))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.activities.index', compact('activities', 'academicYears', 'academicYear', 'statusCounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $faculties = Faculty::with('majors')->orderBy('name_th')->get();
        $activity = new Activity;

        return view('admin.activities.create', compact('faculties', 'activity'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityRequest $request)
    {
        $validated = $request->validated();

        $activity = new Activity($validated);
        $activity->created_by = $request->user()->id;
        $this->ensureQrSecret($activity);

        if ($request->hasFile('banner')) {
            $activity->banner_url = $request->file('banner')->store('activity-banners', 'public');
        }

        DB::transaction(function () use ($activity) {
            $activity->activity_seq = $this->activityCodes->nextSequence($activity->academic_year);
            $activity->activity_code = $this->activityCodes->format(
                $activity->academic_year,
                $activity->activity_category,
                $activity->activity_seq,
            );

            $activity->save();
        });

        $this->syncRestrictions(
            $activity,
            $request->input('faculty_ids', []),
            $request->input('major_ids', []),
            $request->input('target_years', []),
        );

        // Skip draft/cancelled/full — nothing yet for students to act on, so
        // notifying now would just be noise (and full has no room anyway).
        if (in_array($activity->status, ['open', 'ongoing'], true)) {
            $this->notifyEligibleStudentsOfNewActivity($activity);
        }

        return redirect()
            ->route('admin.activities.index')
            ->with('status', __('สร้างกิจกรรมสำเร็จ'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity)
    {
        $faculties = Faculty::with('majors')->orderBy('name_th')->get();
        $activity->load('restrictions');

        return view('admin.activities.edit', compact('activity', 'faculties'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreActivityRequest $request, Activity $activity)
    {
        $wasClosed = $activity->status === 'closed';
        $before = $this->significantSnapshot($activity);

        $validated = $request->validated();

        $activity->fill($validated);
        $this->ensureQrSecret($activity);

        $significantlyChanged = $before !== $this->significantSnapshot($activity);
        if ($significantlyChanged) {
            $activity->important_updated_at = now();
        }

        if ($request->hasFile('banner')) {
            if ($activity->banner_url) {
                Storage::disk('public')->delete($activity->banner_url);
            }
            $activity->banner_url = $request->file('banner')->store('activity-banners', 'public');
        }

        $activity->save();

        $activity->restrictions()->delete();
        $this->syncRestrictions(
            $activity,
            $request->input('faculty_ids', []),
            $request->input('major_ids', []),
            $request->input('target_years', []),
        );

        // Only once restrictions are back in their final state so
        // eligibility is computed correctly for both notifications below.
        if ($significantlyChanged) {
            $this->notifyEligibleStudentsOfUpdate($activity);
        }

        // Only fires on the transition into 'closed' (not on every subsequent
        // edit of an already-closed activity).
        if ($activity->status === 'closed' && ! $wasClosed) {
            $this->notifyMissingStudents($activity);
        }

        return redirect()
            ->route('admin.activities.index')
            ->with('status', __('บันทึกการแก้ไขกิจกรรมสำเร็จ'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity)
    {
        if ($activity->attendances()->exists()) {
            return redirect()
                ->route('admin.activities.index')
                ->with('error', __('ไม่สามารถลบกิจกรรม ":title" ได้ เนื่องจากมีนักศึกษาเช็คชื่อเข้าร่วมแล้ว', ['title' => $activity->title]));
        }

        if ($activity->banner_url) {
            Storage::disk('public')->delete($activity->banner_url);
        }

        $activity->delete();

        return redirect()
            ->route('admin.activities.index')
            ->with('status', __('ลบกิจกรรมสำเร็จ'));
    }

    /**
     * Recurring events (orientation every semester, a monthly blood drive)
     * used to mean retyping every field from scratch — this copies
     * everything except the parts that must be unique per event (code, QR
     * secret, status) and drops the new copy straight into edit. start_at/
     * end_at/checkin windows are copied as-is rather than left blank —
     * the columns are NOT NULL with no default — so the admin's very first
     * action on the copy is correcting the (obviously stale) date, not
     * fighting a save error.
     */
    public function duplicate(Request $request, Activity $activity)
    {
        $activity->load('restrictions');

        $copy = new Activity($activity->only([
            'title', 'description', 'organizer_name', 'dress_code',
            'activity_level', 'activity_category', 'activity_type',
            'academic_year', 'semester', 'credit_hours', 'capacity',
            'location_name', 'location_lat', 'location_lng', 'allowed_radius',
            'checkin_method', 'start_at', 'end_at', 'checkin_opens_at', 'checkin_closes_at',
        ]));
        $copy->title = __(':title (สำเนา)', ['title' => $activity->title]);
        $copy->status = 'draft';
        $copy->created_by = $request->user()->id;
        $this->ensureQrSecret($copy);

        DB::transaction(function () use ($copy, $activity) {
            $copy->activity_seq = $this->activityCodes->nextSequence($copy->academic_year);
            $copy->activity_code = $this->activityCodes->format(
                $copy->academic_year,
                $copy->activity_category,
                $copy->activity_seq,
            );

            $copy->save();

            foreach ($activity->restrictions as $restriction) {
                ActivityRestriction::create([
                    'activity_id' => $copy->id,
                    'faculty_id' => $restriction->faculty_id,
                    'major_id' => $restriction->major_id,
                    'target_year' => $restriction->target_year,
                ]);
            }
        });

        return redirect()
            ->route('admin.activities.edit', $copy)
            ->with('status', __('คัดลอกกิจกรรมสำเร็จ กรุณาตรวจสอบวันเวลาก่อนเผยแพร่'));
    }

    /**
     * Comparable snapshot of the fields a student would actually care about
     * — time, place, and how to check in. Deliberately excludes 'status'
     * since a transition into 'closed' already gets its own more specific
     * ActivityMissed notification. Dates are truncated to the minute
     * because the edit form's datetime-local inputs don't carry seconds —
     * comparing full Carbon equality would treat every single save as a
     * "change" once the original timestamp (e.g. from now()) has non-zero
     * seconds.
     *
     * @return array<string, string|null>
     */
    protected function significantSnapshot(Activity $activity): array
    {
        return [
            'start_at' => $activity->start_at?->format('Y-m-d H:i'),
            'end_at' => $activity->end_at?->format('Y-m-d H:i'),
            'location_name' => $activity->location_name,
            'location_lat' => $activity->location_lat,
            'location_lng' => $activity->location_lng,
            'checkin_method' => $activity->checkin_method,
            'checkin_opens_at' => $activity->checkin_opens_at?->format('Y-m-d H:i'),
            'checkin_closes_at' => $activity->checkin_closes_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Notify every eligible student the moment a newly created activity is
     * actually joinable, so it shows up on their bell right away instead of
     * only being discoverable by browsing the activities list.
     */
    protected function notifyEligibleStudentsOfNewActivity(Activity $activity): void
    {
        $students = $activity->eligibleStudentsQuery()->get();

        if ($students->isNotEmpty()) {
            SafeNotifier::send($students, new ActivityCreated($activity));
        }
    }

    /**
     * Notify every eligible student who never checked in once an activity
     * is marked closed, so they know they missed it and can still submit a
     * late check-in request while it's fresh in mind.
     */
    protected function notifyMissingStudents(Activity $activity): void
    {
        $missing = $activity->missingStudentsQuery()->get();

        if ($missing->isNotEmpty()) {
            SafeNotifier::send($missing, new ActivityMissed($activity));
        }
    }

    /**
     * Notify every currently-eligible student (regardless of whether they've
     * already checked in) when time/location/check-in method changes —
     * eligibility is evaluated fresh here since the audience itself may have
     * changed in the same edit.
     */
    protected function notifyEligibleStudentsOfUpdate(Activity $activity): void
    {
        $students = $activity->eligibleStudentsQuery()->get();

        if ($students->isNotEmpty()) {
            SafeNotifier::send($students, new ActivityUpdated($activity));
        }
    }

    /**
     * QR tokens only make sense for the realtime check-in method — self_report
     * activities never display a QR code. Only mints a secret when one is
     * actually needed and missing, so switching an activity back to realtime
     * later still works without a manual re-save.
     */
    protected function ensureQrSecret(Activity $activity): void
    {
        if ($activity->checkin_method === 'realtime' && ! $activity->qr_secret) {
            $activity->qr_secret = Str::random(40);
        }
    }

    /**
     * Expand the independently multi-selected faculty/major/year audience
     * facets into concrete activity_restrictions rows (cartesian product),
     * so eligibility is AND-across-facet, OR-within-facet. An entirely empty
     * selection means the activity is open to the whole university.
     *
     * @param  array<int>  $facultyIds
     * @param  array<int>  $majorIds
     * @param  array<int>  $targetYears
     */
    protected function syncRestrictions(Activity $activity, array $facultyIds, array $majorIds, array $targetYears): void
    {
        if (empty($facultyIds) && empty($majorIds) && empty($targetYears)) {
            return;
        }

        $faculties = empty($facultyIds) ? [null] : $facultyIds;
        $majors = empty($majorIds) ? [null] : $majorIds;
        $years = empty($targetYears) ? [null] : $targetYears;

        $rows = [];
        foreach ($faculties as $facultyId) {
            foreach ($majors as $majorId) {
                foreach ($years as $targetYear) {
                    $rows[] = [
                        'activity_id' => $activity->id,
                        'faculty_id' => $facultyId,
                        'major_id' => $majorId,
                        'target_year' => $targetYear,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        ActivityRestriction::insert($rows);
    }
}
