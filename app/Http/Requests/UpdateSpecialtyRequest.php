<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecialtyRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Get the current specialty ID from the route so the unique rule can ignore it.
        $specialtyId = $this->route('specialty')?->id;

        return [
            // The name is optional, but if present it must be unique among specialties.
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('specialties', 'name')->ignore($specialtyId),
            ],
            // The description is optional and may be null.
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            // The active flag is optional and must be a boolean value.
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
