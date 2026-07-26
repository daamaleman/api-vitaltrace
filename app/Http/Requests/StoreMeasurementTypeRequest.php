<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating a measurement type.
 */
class StoreMeasurementTypeRequest extends FormRequest
{
    /**
     * Authorization is handled by policies/middleware at the route level.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:measurement_types,name'],
            'base_unit' => ['required', 'string', 'max:30'],
            'decimals' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'The measurement type name is already registered.',
        ];
    }
}
