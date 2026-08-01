<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GraduationCriteria;
use App\Services\ActivityEvaluationService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Manages GraduationCriteria — the per-cohort (enrollment_year +
 * program_type) activity/hour requirements a student is evaluated against.
 * Each "criteria set" in this UI covers both program types for one
 * enrollment year at once (matching how the university actually publishes
 * a requirement per intake year), stored as two GraduationCriteria rows.
 */
class SettingsController extends Controller
{
    private const VALIDATION_RULES = [
        'normal.required_activities' => ['required', 'integer', 'min:1', 'max:200'],
        'normal.required_hours' => ['required', 'integer', 'min:1', 'max:2000'],
        'normal.yearly_targets.*' => ['required', 'integer', 'min:0', 'max:500'],
        'special.required_activities' => ['required', 'integer', 'min:1', 'max:200'],
        'special.required_hours' => ['required', 'integer', 'min:1', 'max:2000'],
        'special.yearly_targets.*' => ['required', 'integer', 'min:0', 'max:500'],
    ];

    public function index()
    {
        $years = GraduationCriteria::orderByDesc('enrollment_year')
            ->get()
            ->groupBy('enrollment_year')
            ->map(fn ($rows) => $rows->keyBy('program_type'));

        return view('admin.settings.index', [
            'years' => $years,
            'defaultCriteria' => ActivityEvaluationService::DEFAULT_CRITERIA,
        ]);
    }

    public function create()
    {
        // Same default-filled shape edit() below hands to the form — a
        // fresh cohort starts from DEFAULT_CRITERIA rather than blank
        // fields, since that's almost always closer to what the university
        // actually wants than zeros.
        return view('admin.settings.create', ['criteria' => ActivityEvaluationService::DEFAULT_CRITERIA]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enrollment_year' => [
                'required', 'integer', 'min:2500', 'max:2600',
                Rule::unique('graduation_criteria', 'enrollment_year'),
            ],
            ...self::VALIDATION_RULES,
        ]);

        $this->saveBothProgramTypes((int) $validated['enrollment_year'], $validated);

        AuditLogger::log('created', __('เกณฑ์การจบการศึกษา'), __('เพิ่มเกณฑ์รหัสนักศึกษาปี :year', ['year' => $validated['enrollment_year']]));

        return redirect()->route('admin.settings.index')->with('status', __('เพิ่มเกณฑ์การจบการศึกษาสำเร็จ'));
    }

    public function edit(int $year)
    {
        $rows = GraduationCriteria::where('enrollment_year', $year)->get()->keyBy('program_type');

        abort_if($rows->isEmpty(), 404);

        $criteria = [
            'normal' => $this->rowToArray($rows->get('normal')),
            'special' => $this->rowToArray($rows->get('special')),
        ];

        return view('admin.settings.edit', compact('year', 'criteria'));
    }

    public function update(Request $request, int $year)
    {
        abort_if(GraduationCriteria::where('enrollment_year', $year)->doesntExist(), 404);

        $validated = $request->validate(self::VALIDATION_RULES);

        $this->saveBothProgramTypes($year, $validated);

        AuditLogger::log('updated', __('เกณฑ์การจบการศึกษา'), __('รหัสนักศึกษาปี :year — ภาคปกติ :np กิจกรรม/:nh ชม., ภาคพิเศษ :sp กิจกรรม/:sh ชม.', [
            'year' => $year,
            'np' => $validated['normal']['required_activities'],
            'nh' => $validated['normal']['required_hours'],
            'sp' => $validated['special']['required_activities'],
            'sh' => $validated['special']['required_hours'],
        ]));

        return redirect()->route('admin.settings.index')->with('status', __('บันทึกเกณฑ์การจบการศึกษาสำเร็จ'));
    }

    public function destroy(int $year)
    {
        $deleted = GraduationCriteria::where('enrollment_year', $year)->delete();

        abort_if($deleted === 0, 404);

        AuditLogger::log('deleted', __('เกณฑ์การจบการศึกษา'), __('ลบเกณฑ์รหัสนักศึกษาปี :year (กลับไปใช้เกณฑ์ปีก่อนหน้าหรือค่าเริ่มต้น)', ['year' => $year]));

        return back()->with('status', __('ลบเกณฑ์รหัสนักศึกษาปี :year แล้ว', ['year' => $year]));
    }

    private function saveBothProgramTypes(int $year, array $validated): void
    {
        foreach (['normal', 'special'] as $type) {
            GraduationCriteria::updateOrCreate(
                ['enrollment_year' => $year, 'program_type' => $type],
                [
                    'required_activities' => (int) $validated[$type]['required_activities'],
                    'required_hours' => (int) $validated[$type]['required_hours'],
                    'yearly_targets' => array_map('intval', $validated[$type]['yearly_targets']),
                ]
            );
        }
    }

    private function rowToArray(?GraduationCriteria $row): array
    {
        if (! $row) {
            return ActivityEvaluationService::DEFAULT_CRITERIA['normal'];
        }

        return [
            'required_activities' => $row->required_activities,
            'required_hours' => $row->required_hours,
            'yearly_targets' => $row->yearly_targets,
        ];
    }
}
