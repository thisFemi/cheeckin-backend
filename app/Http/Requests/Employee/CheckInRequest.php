<?php

namespace App\Http\Requests\Employee;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
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
          'latitude'   => ['required', 'numeric', 'between:-90,90'],
            'longitude'  => ['required', 'numeric', 'between:-180,180'],
            'face_image' => [
                // Required only if policy demands it — enforced in controller
                // since we need the loaded policy to decide. Here: conditionally required.
                'nullable', 'string',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $data = preg_replace('/^data:image\/\w+;base64,/', '', $value);
                        if (base64_decode($data, true) === false) {
                            $fail('The face image must be a valid base64-encoded image.');
                        }
                    }
                },
        ]];
    }
}
