<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
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
            'qr_token' => ['required', 'string'],
            // Not always sent — the client skips asking for GPS at all when
            // the scanned activity doesn't require it (see the
            // checkin-requirements lookup before this step). Whether it's
            // actually required for this specific activity is enforced in
            // AttendanceAutomationService::checkIn(), not here.
            'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'device_uuid' => ['required', 'string', 'max:100'],
            // The client compresses the selfie before upload, so this is a
            // generous backstop rather than the expected size.
            'photo' => ['required', 'image', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => __('กรุณาถ่ายภาพเซลฟีก่อนส่ง'),
            'photo.image' => __('ไฟล์ที่ส่งมาไม่ใช่รูปภาพ กรุณาถ่ายเซลฟีใหม่อีกครั้ง'),
            'photo.max' => __('ไฟล์รูปภาพมีขนาดใหญ่เกินไป กรุณาลองถ่ายใหม่อีกครั้ง'),
        ];
    }
}
