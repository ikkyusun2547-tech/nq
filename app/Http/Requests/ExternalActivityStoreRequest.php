<?php

namespace App\Http\Requests;

use App\Models\ExternalActivityRequest;
use App\Services\AcademicYearCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExternalActivityStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
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
            'organization' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date', 'before_or_equal:today'],
            'activity_category' => ['required', Rule::in(['culture', 'academic', 'sports', 'volunteer', 'ethics'])],
            'hours_requested' => ['required', 'integer', 'min:1', 'max:200'],
            'proof_image' => ['required', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    /**
     * Enforce ExternalActivityRequest::ANNUAL_HOUR_CAP hours/academic year.
     * Checked against both the academic year the activity happened in and
     * the academic year it's being submitted in, so a request is only
     * accepted when neither quota has room — closes the loophole of
     * back-dating an activity into a year that still has room left.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['activity_date', 'hours_requested'])) {
                return;
            }

            $cap = ExternalActivityRequest::ANNUAL_HOUR_CAP;
            $hoursRequested = (int) $this->input('hours_requested');
            $activityDate = Carbon::parse($this->input('activity_date'));

            $activityYear = AcademicYearCalculator::forDate($activityDate);
            $submissionYear = AcademicYearCalculator::forDate(now());

            $usedForActivityYear = ExternalActivityRequest::hoursUsedInAcademicYear(
                $this->user()->id, $activityYear, 'activity_date'
            );
            $usedForSubmissionYear = ExternalActivityRequest::hoursUsedInAcademicYear(
                $this->user()->id, $submissionYear, 'created_at'
            );

            $used = max($usedForActivityYear, $usedForSubmissionYear);

            if ($used + $hoursRequested > $cap) {
                $remaining = max(0, $cap - $used);

                $validator->errors()->add('hours_requested', __(
                    'เกินโควตาคำร้องกิจกรรมภายนอก :cap ชั่วโมงต่อปีการศึกษา (คงเหลือ :remaining ชั่วโมง)',
                    ['cap' => $cap, 'remaining' => $remaining]
                ));
            }
        });
    }
}
