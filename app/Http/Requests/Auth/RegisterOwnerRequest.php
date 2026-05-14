<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterOwnerRequest extends FormRequest
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
            'first_name'         => ['required', 'string', 'max:100'],
            'last_name'          => ['required', 'string', 'max:100'],
            'email'              => ['required', 'email', 'unique:users,email'],
            'password'           => ['required', 'string', 'min:8', ],
            'phone'              => ['nullable', 'string', 'max:20'],
            'organization_name'  => ['required', 'string', 'unique:organizations,name'],
            'organization_email' => ['required', 'email', 'unique:organizations,email'],
            'organization_phone' => ['nullable', 'string', 'max:20'],
            'organization_address' => ['nullable', 'string', 'max:500'],
       
        ];
    }

   
}
