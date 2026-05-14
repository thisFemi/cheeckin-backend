<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterEmployeeRequest extends FormRequest
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
            'password'   => ['required', 'string', 'min:8' ],
            'phone'      => ['nullable', 'string', 'max:20'],
            'org_code'   => ['required', 'string', 'exists:organizations,org_code'],
            'department'   => ['nullable', 'string', 'max:100'],
            'employee_id'  => ['nullable', 'string', 'max:50'],
            'joined_date'  => ['nullable', 'date'],
            'position'     => ['nullable', 'string', 'max:100'],
        ];
    }
     public function messages(): array
    {
        return [
            'org_code.exists' => 'The organization code is invalid or does not exist.',
        ];
    }
}
