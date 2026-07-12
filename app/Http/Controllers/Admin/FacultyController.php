<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacultyController extends Controller
{
    public function index()
    {
        $faculties = Faculty::withCount(['majors', 'users'])->orderBy('name_th')->get();

        return view('admin.faculties.index', compact('faculties'));
    }

    public function create()
    {
        return view('admin.faculties.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:faculties,code'],
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $faculty = Faculty::create($validated);
        AuditLogger::log('created', __('คณะ'), $faculty->name_th);

        return redirect()->route('admin.faculties.index')->with('status', __('เพิ่มคณะสำเร็จ'));
    }

    public function edit(Faculty $faculty)
    {
        $faculty->load('majors');

        return view('admin.faculties.edit', compact('faculty'));
    }

    public function update(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('faculties', 'code')->ignore($faculty->id)],
            'name_th' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ]);

        $faculty->update($validated);
        AuditLogger::log('updated', __('คณะ'), $faculty->name_th);

        return redirect()->route('admin.faculties.edit', $faculty)->with('status', __('บันทึกข้อมูลคณะสำเร็จ'));
    }

    public function destroy(Faculty $faculty)
    {
        if ($faculty->users()->exists() || $faculty->majors()->exists()) {
            return back()->with('error', __('ไม่สามารถลบคณะ ":name" ได้ เนื่องจากยังมีนักศึกษาหรือสาขาผูกอยู่', ['name' => $faculty->name_th]));
        }

        $name = $faculty->name_th;
        $faculty->delete();
        AuditLogger::log('deleted', __('คณะ'), $name);

        return redirect()->route('admin.faculties.index')->with('status', __('ลบคณะสำเร็จ'));
    }
}
