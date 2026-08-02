<?php

namespace App\Http\Requests;

use App\Models\CreditTransferPosition;
use App\Models\CreditTransferRequest;
use App\Services\AcademicYearCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminCreditTransferGrantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'super_admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'position' => ['required', Rule::in(array_keys(CreditTransferPosition::hoursMap()))],
            'academic_year' => ['required', 'integer', 'min:2560', 'max:'.AcademicYearCalculator::forDate(now())],
            'activity_category' => ['required', Rule::in(['culture', 'academic', 'sports', 'volunteer', 'ethics'])],
            'hours_approved' => ['nullable', 'integer', 'min:0', 'max:200'],
            'proof_image' => ['nullable', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    /**
     * Same "1 credit-transfer claim per academic year" quota
     * (CreditTransferStoreRequest) applies here — an admin grant and a
     * student's own claim share the same yearly slot.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['position', 'academic_year'])) {
                return;
            }

            $student = $this->route('student');

            if (CreditTransferRequest::hasClaimedAcademicYear($student->id, (int) $this->input('academic_year'))) {
                $validator->errors()->add('academic_year', __(
                    'นักศึกษาคนนี้มีการเทียบโอนชั่วโมงสำหรับปีการศึกษานี้ไปแล้ว (ได้ 1 ครั้งต่อปีการศึกษา)'
                ));
            }
        });
    }
}
