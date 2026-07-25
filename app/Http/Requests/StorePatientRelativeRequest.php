<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PatientRelative;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientRelativeRequest extends FormRequest
{
    /**
     * Determine whether the current user is authorized to submit this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for storing a patient-relative relation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Required foreign keys.
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'relative_id' => ['required', 'integer', 'exists:relatives,id'],

            // Relation details.
            'relationship' => ['required', 'string', 'max:50'],
            'scope' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:PENDING,ACTIVE,REVOKED,EXPIRED'],

            // Validity period.
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            // Audit fields.
            'registered_by' => ['required', 'integer', 'exists:users,id'],
            'authorized_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Attach custom post-validation business rules.
     *
     * 1) Avoid duplicate patient-relative links.
     * 2) Enforce a maximum of two active relatives per patient.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $patientId = $this->input('patient_id');
            $relativeId = $this->input('relative_id');

            // Prevent duplicate patient-relative relation (composite unique guard).
            $duplicate = PatientRelative::query()
                ->where('patient_id', $patientId)
                ->where('relative_id', $relativeId)
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('relative_id', 'This relative is already linked to the patient.');

                return;
            }

            // RN-03: Maximum two active relatives per patient.
            $activeCount = PatientRelative::query()
                ->where('patient_id', $patientId)
                ->whereIn('status', PatientRelative::ACTIVE_STATUSES)
                ->count();

            if ($activeCount >= 2) {
                $validator->errors()->add('patient_id', 'The patient already has the maximum of two active relatives.');
            }
        });
    }
}
