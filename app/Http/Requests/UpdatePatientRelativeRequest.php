<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PatientRelative;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation for updating a patient relative record.
 */
class UpdatePatientRelativeRequest extends FormRequest
{
    /**
     * Determine whether the current user can perform this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for partial updates.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'relationship' => ['sometimes', 'string', 'max:50'],
            'scope' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:PENDING,ACTIVE,REVOKED,EXPIRED'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'authorized_by' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Add post-validation business rules.
     *
     * RN-03: when re-activating an inactive relative, the patient cannot exceed
     * two active relatives.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $newStatus = $this->input('status');

            // Continue only if the requested status is considered active.
            if (! in_array($newStatus, PatientRelative::ACTIVE_STATUSES, true)) {
                return;
            }

            $current = $this->route('patient_relative');

            // Skip if route model is missing or already active.
            if ($current === null || in_array($current->status, PatientRelative::ACTIVE_STATUSES, true)) {
                return;
            }

            // Count other active relatives for the same patient.
            $activeCount = PatientRelative::query()
                ->where('patient_id', $current->patient_id)
                ->whereIn('status', PatientRelative::ACTIVE_STATUSES)
                ->where('id', '!=', $current->id)
                ->count();

            // Block update if the maximum number of active relatives is reached.
            if ($activeCount >= 2) {
                $validator->errors()->add('status', 'The patient already has the maximum of two active relatives.');
            }
        });
    }
}
