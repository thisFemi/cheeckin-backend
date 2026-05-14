<?php

namespace App\Http\Requests\Owner;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
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
        'first_name' => ['nullable', 'string', 'max:100'],
        'last_name'  => ['nullable', 'string', 'max:100'],
        'phone'      => ['nullable', 'string', 'max:20'],
        'org_code'   => ['required', 'string', 'exists:organizations,org_code'],
        'department' => ['nullable', 'string', 'max:100'],
        'employee_id'=> ['required', 'string', 'max:50'],
        'joined_date'=> ['nullable', 'date'],
        'position'   => ['nullable', 'string', 'max:100'],
    ];
    }
}
