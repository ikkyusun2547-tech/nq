<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'proof_image' => ['required', 'image', 'max:2048'],
        ];
    }
}
