<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'banner' => ['nullable', 'image', 'max:2048'],
            'organizer_name' => ['nullable', 'string', 'max:255'],
            'dress_code' => ['nullable', 'string', 'max:255'],
            'activity_level' => ['required', Rule::in(['university', 'faculty'])],
            'activity_category' => ['required', Rule::in(['culture', 'academic', 'sports', 'volunteer', 'ethics'])],
            'activity_type' => ['required', Rule::in(['core', 'elective', 'practice'])],
            'academic_year' => ['required', 'integer', 'min:2540', 'max:'.((int) date('Y') + 544)],
            'semester' => ['required', Rule::in(['1', '2', '3'])],
            'credit_hours' => ['required', 'integer', 'min:1', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'location_name' => ['required', 'string', 'max:255'],
            'checkin_method' => ['required', Rule::in(['realtime', 'self_report'])],
            // Thailand's bounding box, roughly: lat 5.6-20.5N, lng 97.3-105.7E
            'location_lat' => [Rule::requiredIf(fn () => $this->input('checkin_method') === 'realtime'), 'nullable', 'numeric', 'between:5.6,20.5'],
            'location_lng' => [Rule::requiredIf(fn () => $this->input('checkin_method') === 'realtime'), 'nullable', 'numeric', 'between:97.3,105.7'],
            'allowed_radius' => [Rule::requiredIf(fn () => $this->input('checkin_method') === 'realtime'), 'nullable', 'integer', 'min:10', 'max:5000'],
            'checkin_opens_at' => [Rule::requiredIf(fn () => $this->input('checkin_method') === 'self_report'), 'nullable', 'date'],
            'checkin_closes_at' => [Rule::requiredIf(fn () => $this->input('checkin_method') === 'self_report'), 'nullable', 'date', 'after:checkin_opens_at'],
            'status' => ['required', Rule::in(['draft', 'open', 'full', 'ongoing', 'closed', 'cancelled'])],

            'faculty_ids' => ['nullable', 'array'],
            'faculty_ids.*' => ['integer', 'exists:faculties,id'],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => ['integer', 'exists:majors,id'],
            'target_years' => ['nullable', 'array'],
            'target_years.*' => ['integer', 'between:1,4'],
        ];
    }

    /**
     * Institutional rule: core (บังคับแกน) activities are fixed at 5 credit
     * hours regardless of what the client submits.
     */
    protected function passedValidation(): void
    {
        if ($this->input('activity_type') === 'core') {
            $this->merge(['credit_hours' => 5]);
        }
    }
}
