<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignLeaveRequest extends FormRequest
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
                'leave_types'                => ['required', 'array', 'min:1'],
            'leave_types.*.leave_type_id' => [
                'required', 'integer',
                Rule::exists('leave_types', 'id')
                    ->where('organization_id', $this->user()->organization_id),
            ],
            'leave_types.*.entitled_days' => ['required', 'integer', 'min:1', 'max:365'],
        
        ];
    }
}
