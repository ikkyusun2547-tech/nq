<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileSetupStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
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
                Rule::exists('majors', 'id')->where('faculty_id', $this->input('faculty_id')),
            ],
        ];
    }
}
