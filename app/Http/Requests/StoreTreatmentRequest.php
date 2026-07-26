<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for prescribing a treatment.
 */
class StoreTreatmentRequest extends FormRequest
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
            'diagnosis_id' => ['nullable', 'integer', 'exists:diagnoses,id'],
            'indications' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,FINISHED,SUSPENDED'],
            'prescribed_by' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
