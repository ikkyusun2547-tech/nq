<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityEvaluationService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(ActivityEvaluationService $evaluator)
    {
        $criteria = $evaluator->criteria();

        return view('admin.settings.edit', compact('criteria'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'normal.required_activities' => ['required', 'integer', 'min:1', 'max:200'],
            'normal.required_hours' => ['required', 'integer', 'min:1', 'max:2000'],
            'normal.yearly_targets.*' => ['required', 'integer', 'min:0', 'max:500'],
            'special.required_activities' => ['required', 'integer', 'min:1', 'max:200'],
            'special.required_hours' => ['required', 'integer', 'min:1', 'max:2000'],
            'special.yearly_targets.*' => ['required', 'integer', 'min:0', 'max:500'],
        ]);

        $criteria = [
            'normal' => [
                'required_activities' => (int) $validated['normal']['required_activities'],
                'required_hours' => (int) $validated['normal']['required_hours'],
                'yearly_targets' => array_map('intval', $validated['normal']['yearly_targets']),
            ],
            'special' => [
                'required_activities' => (int) $validated['special']['required_activities'],
                'required_hours' => (int) $validated['special']['required_hours'],
                'yearly_targets' => array_map('intval', $validated['special']['yearly_targets']),
            ],
        ];

        Setting::setJson(ActivityEvaluationService::SETTINGS_KEY, $criteria);

        return back()->with('status', __('บันทึกเกณฑ์การจบการศึกษาสำเร็จ'));
    }
}
