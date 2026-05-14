<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
// use Illuminate\Validation\ValidationException;
// use App\Exceptions\Handler.php

class UpdateLeaveTypeRequest extends FormRequest
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
            'name'              => ['required', 'string', 'max:100'],
            'code'              => ['nullable', 'string', 'max:10'],
            'description'       => ['nullable', 'string', 'max:500'],
            'days_per_year'     => ['sometimes', 'integer', 'min:1', 'max:365'],
            'is_paid'           => ['sometimes', 'in:true,false,1,0'],
            'requires_document' => ['sometimes', 'in:true,false,1,0'],
            'is_active'         => ['sometimes', 'in:true,false,1,0'],
        ];}
}
