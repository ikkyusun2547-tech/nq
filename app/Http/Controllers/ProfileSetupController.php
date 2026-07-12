<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileSetupController extends Controller
{
    /**
     * Also serves as the "edit profile" form for students who already
     * completed setup — store() happily overwrites an existing profile, but
     * until now the form always rendered blank, so re-visiting to fix a typo
     * meant retyping everything. Pre-fill from the user record (old() still
     * wins on validation redisplay).
     */
    public function show()
    {
        $faculties = Faculty::with('majors')->orderBy('name_th')->get();
        $user = Auth::user();

        [$namePrefix, $firstName, $lastName] = $this->splitName($user->name_thai);

        return view('profile-setup.show', compact('faculties', 'user', 'namePrefix', 'firstName', 'lastName'));
    }

    /**
     * name_thai is stored pre-joined as prefix+first+' '+last (see store()
     * below) with no separate columns, so editing needs a best-effort
     * reverse split. Order matters: "นางสาว" must be checked before "นาง"
     * since the latter is a leading substring of the former.
     */
    private function splitName(?string $nameThai): array
    {
        if (! $nameThai) {
            return [null, null, null];
        }

        foreach (['นางสาว', 'นาย', 'นาง'] as $prefix) {
            if (str_starts_with($nameThai, $prefix)) {
                $rest = trim(substr($nameThai, strlen($prefix)));

                return [$prefix, ...array_pad(explode(' ', $rest, 2), 2, null)];
            }
        }

        return [null, null, null];
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $wasComplete = $user->hasCompletedProfile();

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

        return redirect()
            ->route($wasComplete ? 'profile.show' : 'dashboard')
            ->with('status', __('บันทึกข้อมูลโปรไฟล์สำเร็จ'));
    }
}
