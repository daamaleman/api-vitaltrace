<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHealthStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('SYSTEM_ADMIN') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'professional_type' => ['prohibited'],
            'person_id' => ['prohibited'],
            'professional_code' => [
                'sometimes', 'string', 'max:50',
                Rule::unique('health_staff', 'professional_code')->ignore($this->route('health_staff')?->id),
            ],
            'specialty_id' => ['sometimes', 'nullable', 'integer', 'exists:specialties,id'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
