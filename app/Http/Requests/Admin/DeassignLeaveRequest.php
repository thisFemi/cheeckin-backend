<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeassignLeaveRequest extends FormRequest
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
            'leave_type_ids'   => ['required', 'array', 'min:1'],
            'leave_type_ids.*' => ['integer', 'exists:leave_types,id'],
            'year'             => ['nullable', 'integer', 'min:2020', 'max:2100'],
        ];
    }
}
