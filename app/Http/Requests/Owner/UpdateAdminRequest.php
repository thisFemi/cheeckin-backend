<?php

namespace App\Http\Requests\Owner;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'  => ['sometimes', 'string', 'max:100'],
            'last_name'   => ['sometimes', 'string', 'max:100'],
            'email'       => [
                'sometimes',
                'email',
                // Ignore the current admin's own email when checking uniqueness
                Rule::unique('users', 'email')->ignore($this->route('admin')),
            ],
            'phone'       => ['nullable', 'string', 'max:20'],
            'department'  => ['nullable', 'string', 'max:100'],
            'position'    => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'joined_date' => ['nullable', 'date'],
            'role_id'     => [
                'nullable',
                'integer',
                // Role must belong to this organization
                Rule::exists('roles', 'id')
                    ->where('organization_id', $this->user()->organization_id),
            ],
            'employment_status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'suspended']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.exists' => 'The selected role does not belong to your organization.',
            'email.unique'   => 'This email address is already taken.',
        ];
    }
}