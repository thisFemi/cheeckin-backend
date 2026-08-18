<?php

namespace App\Http\Requests\Owner;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAdminRequest extends FormRequest
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
        'department' => ['nullable', 'string', 'max:100'],
        'position'   => ['nullable', 'string', 'max:100'],
        'role_id'    => [
            'nullable',
            'integer',
            // Role must belong to this organization
            Rule::exists('roles', 'id')
                ->where('organization_id', $this->user()->organization_id),
        ],
    ];
    }
}
