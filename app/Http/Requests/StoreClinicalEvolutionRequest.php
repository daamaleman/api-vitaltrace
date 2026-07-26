<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for registering a clinical evolution entry.
 */
class StoreClinicalEvolutionRequest extends FormRequest
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
            'registered_by' => ['required', 'integer', 'exists:users,id'],
            'clinical_summary' => ['required', 'string'],
            'status' => ['required', 'string', 'in:STABLE,OBSERVATION,DELICATE,CRITICAL,RECOVERY'],
            'recorded_at' => ['required', 'date'],
        ];
    }
}
