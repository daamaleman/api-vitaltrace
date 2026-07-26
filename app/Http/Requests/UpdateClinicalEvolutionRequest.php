<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating a clinical evolution entry.
 *
 * The patient and the registering professional are immutable to preserve the
 * append-only clinical timeline (RN-09, RN-15).
 */
class UpdateClinicalEvolutionRequest extends FormRequest
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
            'clinical_summary' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', 'in:STABLE,OBSERVATION,DELICATE,CRITICAL,RECOVERY'],
            'recorded_at' => ['sometimes', 'date'],
        ];
    }
}
