<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileSetupController extends Controller
{
    public function show()
    {
        $faculties = Faculty::with('majors')->orderBy('name_th')->get();

        return view('profile-setup.show', compact('faculties'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'title_prefix' => ['required', Rule::in(['นาย', 'นาง', 'นางสาว'])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'student_id' => [
                'required', 'digits:11',
                Rule::unique('users', 'student_id')->ignore($user->id),
            ],
            'enrollment_year' => ['required', 'integer', 'min:2540', 'max:'.((int) date('Y') + 543)],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'program_type' => ['required', Rule::in(['normal', 'special'])],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'major_id' => [
                'required',
                Rule::exists('majors', 'id')->where('faculty_id', $request->input('faculty_id')),
            ],
        ]);

        $user->update([
            ...collect($validated)->except(['title_prefix', 'first_name', 'last_name'])->all(),
            'name_thai' => $validated['title_prefix'].$validated['first_name'].' '.$validated['last_name'],
        ]);

        return redirect()->route('dashboard')->with('status', 'บันทึกข้อมูลโปรไฟล์สำเร็จ');
    }
}
