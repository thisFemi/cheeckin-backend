<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLeaveTypeRequest extends FormRequest
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
         'name'               => ['required', 'string', 'max:100'],
            'code'               => [
                'nullable', 'string', 'max:10',
                Rule::unique('leave_types')->where('organization_id', $this->user()->organization_id),
            ],
            'description'        => ['nullable', 'string', 'max:500'],
            'days_per_year'      => ['required', 'integer', 'min:1', 'max:365'],
            'is_paid'            => ['boolean'],
            'requires_document'  => ['boolean'],
        ];
    }
}
