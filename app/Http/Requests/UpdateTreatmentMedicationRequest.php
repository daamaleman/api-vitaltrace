<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating a treatment medication detail.
 *
 * The parent treatment and the medication are immutable; changing them would
 * mean a different prescription, which should be a new record.
 */
class UpdateTreatmentMedicationRequest extends FormRequest
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
            'dose' => ['sometimes', 'string', 'max:80'],
            'route' => ['sometimes', 'string', 'max:50'],
            'frequency' => ['sometimes', 'string', 'max:80'],
            'schedules' => ['sometimes', 'nullable', 'array'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
