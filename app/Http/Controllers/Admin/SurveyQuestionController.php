<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestion;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class SurveyQuestionController extends Controller
{
    public function index()
    {
        $questions = SurveyQuestion::withCount('answers')->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.survey-questions.index', compact('questions'));
    }

    /**
     * Changes only affect surveys answered from now on — past answers keep
     * pointing at the same question row, so rewording one that already has
     * answers changes how those old results read; the view warns about it.
     */
    public function update(Request $request, SurveyQuestion $surveyQuestion)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        if (! $validated['is_active'] && $surveyQuestion->is_active && SurveyQuestion::active()->count() <= 1) {
            return back()->with('error', __('ต้องมีคำถามที่เปิดใช้งานอย่างน้อย 1 ข้อ'));
        }

        $surveyQuestion->update($validated);
        AuditLogger::log('updated', __('คำถามแบบประเมินกิจกรรม'), $surveyQuestion->text);

        return back()->with('status', __('บันทึกคำถามสำเร็จ'));
    }
}
