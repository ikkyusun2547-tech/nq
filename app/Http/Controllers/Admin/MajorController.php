<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Major;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MajorController extends Controller
{
    public function store(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:majors,code'],
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'degree_abbr' => ['nullable', 'string', 'max:30'],
        ]);

        $major = $faculty->majors()->create($validated);
        AuditLogger::log('created', __('สาขา'), $major->name_th);

        return back()->with('status', __('เพิ่มสาขาสำเร็จ'));
    }

    public function update(Request $request, Major $major)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('majors', 'code')->ignore($major->id)],
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'degree_abbr' => ['nullable', 'string', 'max:30'],
        ]);

        $major->update($validated);
        AuditLogger::log('updated', __('สาขา'), $major->name_th);

        return back()->with('status', __('บันทึกข้อมูลสาขาสำเร็จ'));
    }

    public function destroy(Major $major)
    {
        if ($major->users()->exists()) {
            return back()->with('error', __('ไม่สามารถลบสาขา ":name" ได้ เนื่องจากยังมีนักศึกษาอยู่ในสาขานี้', ['name' => $major->name_th]));
        }

        $name = $major->name_th;
        $major->delete();
        AuditLogger::log('deleted', __('สาขา'), $name);

        return back()->with('status', __('ลบสาขาสำเร็จ'));
    }
}
