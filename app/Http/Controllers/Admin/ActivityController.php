<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Models\Activity;
use App\Models\ActivityRestriction;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $activities = Activity::withCount('attendances')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->string('search').'%');
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('academic_year'), fn ($query) => $query->where('academic_year', $request->input('academic_year')))
            ->when($request->filled('semester'), fn ($query) => $query->where('semester', $request->input('semester')))
            ->latest('start_at')
            ->paginate(20)
            ->withQueryString();

        $academicYears = Activity::query()
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        return view('admin.activities.index', compact('activities', 'academicYears'));
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
        $activity->qr_secret = Str::random(40);

        if ($request->hasFile('banner')) {
            $activity->banner_url = $request->file('banner')->store('activity-banners', 'public');
        }

        $activity->save();

        $this->syncRestrictions(
            $activity,
            $request->input('faculty_ids', []),
            $request->input('major_ids', []),
            $request->input('target_years', []),
        );

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
        $validated = $request->validated();

        $activity->fill($validated);

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
                ->with('error', __('ไม่สามารถลบกิจกรรม ":title" ได้ เนื่องจากมีนักศึกษาเช็กชื่อเข้าร่วมแล้ว', ['title' => $activity->title]));
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
