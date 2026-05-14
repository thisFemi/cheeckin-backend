<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
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
            'first_name'        => ['sometimes', 'string', 'max:100'],
            'last_name'         => ['sometimes', 'string', 'max:100'],
            'email'             => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->route('id'))],
            'phone'             => ['nullable', 'string', 'max:20'],
            'employee_id'       => ['nullable', 'string', 'max:50'],
            'department'        => ['nullable', 'string', 'max:100'],
            'position'          => ['nullable', 'string', 'max:100'],
            'joined_date'       => ['nullable', 'date'],
            'role_id'           => ['nullable', 'integer', 'exists:roles,id'],
            'attendance_policy_id' => ['nullable', 'integer', 'exists:attendance_policies,id'],
            'employment_status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }
}
