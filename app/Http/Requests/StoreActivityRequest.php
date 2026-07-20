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
            'requires_gps' => ['boolean'],
            // Thailand's bounding box, roughly: lat 5.6-20.5N, lng 97.3-105.7E
            // Only actually required when the GPS-radius check is in effect
            // (realtime + requires_gps) — a realtime activity that skips GPS
            // needs no pin at all, same as self_report.
            'location_lat' => [Rule::requiredIf(fn () => $this->input('checkin_method') === 'realtime' && $this->boolean('requires_gps')), 'nullable', 'numeric', 'between:5.6,20.5'],
            'location_lng' => [Rule::requiredIf(fn () => $this->input('checkin_method') === 'realtime' && $this->boolean('requires_gps')), 'nullable', 'numeric', 'between:97.3,105.7'],
            'allowed_radius' => [Rule::requiredIf(fn () => $this->input('checkin_method') === 'realtime' && $this->boolean('requires_gps')), 'nullable', 'integer', 'min:10', 'max:5000'],
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
     * An unchecked HTML checkbox submits nothing at all, which $this->boolean()
     * already reads as false — this just makes sure the key always exists so
     * Activity::create()/update() gets an explicit true/false rather than
     * silently keeping whatever the column previously held.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['requires_gps' => $this->boolean('requires_gps')]);
    }

    /**
     * Institutional rule: core (บังคับแกน) activities are fixed at 5 credit
     * hours regardless of what the client submits. Overridden here (rather
     * than merged in passedValidation()) because the controller reads
     * $request->validated(), which snapshots data at Validator-construction
     * time — a later $this->merge() never reaches it.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($key === null && ($validated['activity_type'] ?? null) === 'core') {
            $validated['credit_hours'] = 5;
        }

        return $validated;
    }
}
