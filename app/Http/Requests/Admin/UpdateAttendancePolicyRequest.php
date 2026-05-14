<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendancePolicyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                           => ['sometimes', 'string', 'max:100'],
            'work_start_time'                => ['sometimes', 'date_format:H:i'],
            'work_end_time'                  => ['sometimes', 'date_format:H:i'],
            'late_threshold_minutes'         => ['nullable', 'integer', 'min:0', 'max:120'],
            'early_checkout_threshold_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'allow_remote'                   => ['boolean'],
            'office_latitude'                => ['nullable', 'numeric', 'between:-90,90'],
            'office_longitude'               => ['nullable', 'numeric', 'between:-180,180'],
            'location_radius_meters'         => ['nullable', 'integer', 'min:10', 'max:5000'],
            'require_face_capture'           => ['boolean'],
            'is_active'                      => ['boolean'],
        ];
    }
}
