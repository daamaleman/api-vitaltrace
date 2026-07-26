<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating a measurement.
 *
 * The patient, origin and author are immutable to preserve traceability (RN-15);
 * only the value, unit, time and observation may be corrected.
 */
class UpdateMeasurementRequest extends FormRequest
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
            'measurement_type_id' => ['sometimes', 'integer', 'exists:measurement_types,id'],
            'value' => ['sometimes', 'numeric'],
            'unit' => ['sometimes', 'string', 'max:30'],
            'measured_at' => ['sometimes', 'date'],
            'observation' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
