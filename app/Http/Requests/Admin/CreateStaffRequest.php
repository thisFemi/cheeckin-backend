<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateStaffRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
             'email'      => ['required', 'email', 'unique:users,email'],
             'phone'      => ['nullable', 'string', 'max:20'],
             'department'   => ['nullable', 'string', 'max:100'],
            'employee_id'  => ['nullable', 'string', 'max:50'],
            'joined_date'  => ['nullable', 'date'],
            'position'     => ['nullable', 'string', 'max:100'],
            'attendance_policy_id' => ['nullable', 'exists:attendance_policies,id'],
            'leave_type_ids' => ['nullable', 'array'],
            'leave_type_ids.*' => ['exists:leave_types,id'],
            'requires_face_setup' => ['nullable','boolean'],

        ];
    }
}
