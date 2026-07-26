<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for adding a medication to a treatment.
 */
class StoreTreatmentMedicationRequest extends FormRequest
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
            'treatment_id' => ['required', 'integer', 'exists:treatments,id'],
            'medication_id' => ['required', 'integer', 'exists:medications,id'],
            'dose' => ['required', 'string', 'max:80'],
            'route' => ['required', 'string', 'max:50'],
            'frequency' => ['required', 'string', 'max:80'],
            'schedules' => ['nullable', 'array'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
