<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating an alert.
 */
class StoreAlertRequest extends FormRequest
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
            'measurement_id' => ['nullable', 'integer', 'exists:measurements,id'],
            'type' => ['required', 'string', 'max:80'],
            'severity' => ['required', 'string', 'in:INFORMATIONAL,MODERATE,HIGH,CRITICAL'],
            'status' => ['sometimes', 'string', 'in:NEW,CLASSIFIED,ESCALATED,IN_PROGRESS,CLOSED'],
            'description' => ['required', 'string'],
            'generated_at' => ['required', 'date'],
        ];
    }
}
