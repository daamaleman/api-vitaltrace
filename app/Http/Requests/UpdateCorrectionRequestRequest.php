<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCorrectionRequestRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'field' => ['sometimes', 'string', 'max:100'],
            'current_value' => ['sometimes', 'string', 'max:255'],
            'requested_value' => ['sometimes', 'string', 'max:255'],
            'reason' => ['sometimes', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', 'max:30'],
            'response' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
