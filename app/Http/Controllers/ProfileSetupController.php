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
            'name_thai' => ['required', 'string', 'max:150'],
            'student_id' => [
                'required', 'digits:8',
                Rule::unique('users', 'student_id')->ignore($user->id),
            ],
            'enrollment_year' => ['required', 'integer', 'min:2540', 'max:'.((int) date('Y') + 543)],
            'program_type' => ['required', Rule::in(['normal', 'special'])],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'major_id' => [
                'required',
                Rule::exists('majors', 'id')->where('faculty_id', $request->input('faculty_id')),
            ],
        ]);

        $user->update($validated);

        return redirect()->route('dashboard')->with('status', 'บันทึกข้อมูลโปรไฟล์สำเร็จ');
    }
}
