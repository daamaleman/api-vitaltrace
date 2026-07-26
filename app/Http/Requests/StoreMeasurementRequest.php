<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for registering a measurement.
 */
class StoreMeasurementRequest extends FormRequest
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
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'measurement_type_id' => ['required', 'integer', 'exists:measurement_types,id'],
            'value' => ['required', 'numeric'],
            'unit' => ['required', 'string', 'max:30'],
            'measured_at' => ['required', 'date'],
            'origin' => ['required', 'string', 'in:PATIENT,RELATIVE,DOCTOR,NURSE'],
            'author_user_id' => ['required', 'integer', 'exists:users,id'],
            'observation' => ['nullable', 'string'],
        ];
    }
}
