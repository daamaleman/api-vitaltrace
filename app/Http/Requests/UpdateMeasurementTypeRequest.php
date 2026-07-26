<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules for updating a measurement type.
 */
class UpdateMeasurementTypeRequest extends FormRequest
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
        $measurementTypeId = $this->route('measurement_type')?->id;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('measurement_types', 'name')->ignore($measurementTypeId),
            ],
            'base_unit' => ['sometimes', 'string', 'max:30'],
            'decimals' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
