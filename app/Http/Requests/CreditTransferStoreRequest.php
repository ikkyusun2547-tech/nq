<?php

namespace App\Http\Requests;

use App\Models\CreditTransferRequest;
use App\Services\AcademicYearCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreditTransferStoreRequest extends FormRequest
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
            'position' => ['required', Rule::in(array_keys(CreditTransferRequest::POSITION_HOURS))],
            'academic_year' => ['required', 'integer', 'min:2560', 'max:'.AcademicYearCalculator::forDate(now())],
            'proof_image' => ['required', 'image', 'max:2048'],
        ];
    }

    /**
     * Enforce the "1 credit-transfer request per academic year" quota from
     * ข้อ 14 of the university announcement.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['position', 'academic_year'])) {
                return;
            }

            if (CreditTransferRequest::hasClaimedAcademicYear($this->user()->id, (int) $this->input('academic_year'))) {
                $validator->errors()->add('academic_year', __(
                    'คุณส่งคำร้องเทียบโอนชั่วโมงสำหรับปีการศึกษานี้ไปแล้ว (ขอได้ 1 ครั้งต่อปีการศึกษา)'
                ));
            }
        });
    }
}
