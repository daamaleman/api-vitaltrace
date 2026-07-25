<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ProfessionalAssignment;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating a professional assignment.
 *
 * The patient and professional are immutable; only validity, status and type
 * transitions are allowed. Re-activating a primary doctor re-checks uniqueness.
 */
class UpdateProfessionalAssignmentRequest extends FormRequest
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
            'assignment_type' => ['sometimes', 'string', 'in:PRIMARY_DOCTOR,SECONDARY_DOCTOR,NURSE'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,FINISHED,SUSPENDED'],
            'change_reason' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Re-check the single active primary doctor rule when the resulting
     * assignment would become an active primary doctor.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $current = $this->route('professional_assignment');

            if ($current === null) {
                return;
            }

            $resultingType = $this->input('assignment_type', $current->assignment_type);
            $resultingStatus = $this->input('status', $current->status);

            if (
                $resultingType !== ProfessionalAssignment::TYPE_PRIMARY_DOCTOR
                || $resultingStatus !== ProfessionalAssignment::STATUS_ACTIVE
            ) {
                return;
            }

            $exists = ProfessionalAssignment::query()
                ->where('patient_id', $current->patient_id)
                ->where('assignment_type', ProfessionalAssignment::TYPE_PRIMARY_DOCTOR)
                ->where('status', ProfessionalAssignment::STATUS_ACTIVE)
                ->where('id', '!=', $current->id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('assignment_type', 'The patient already has an active primary doctor.');
            }
        });
    }
}
