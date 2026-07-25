<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    /**
     * Determine whether the current user is authorized to update a patient.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Ignore the current patient when checking for unique record numbers.
        $patientId = $this->route('patient')?->id;

        return [
            // Validate the record number and keep it unique.
            'record_number' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('patients', 'record_number')->ignore($patientId),
            ],
            // Validate the admission date when present.
            'admission_date' => ['sometimes', 'date'],
            // Restrict the administrative status to the allowed values.
            'administrative_status' => ['sometimes', 'string', 'in:PRE_REGISTERED,ACTIVE,INACTIVE,DISCHARGED,ARCHIVED'],
            // Validate the emergency contact name when provided.
            'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'max:160'],
            // Validate the emergency contact phone when provided.
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'max:25'],
            // Validate optional administrative notes.
            'administrative_notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
