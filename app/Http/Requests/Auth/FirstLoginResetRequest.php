<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FirstLoginResetRequest extends FormRequest
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
             'email'            => ['required', 'email', 'exists:users,email'],
           'current_password' => ['required', 'string'],
           'password'         => [
                'required',
                'string',
                'min:8',
                'different:current_password', // new password must differ from temp
            ],
        ];
    }

    public function messages(): array
    {
        return [
                  'email.exists' => 'No account found with this email address.',
            'password.different' => 'Your new password must be different from the temporary password.',
        ];
    }
}
