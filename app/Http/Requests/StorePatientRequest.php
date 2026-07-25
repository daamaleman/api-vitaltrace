<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates incoming data for creating a new patient record.
 */
class StorePatientRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define the validation rules for storing a patient.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:people,id', 'unique:patients,person_id'],
            'record_number' => ['required', 'string', 'max:30', 'unique:patients,record_number'],
            'admission_date' => ['required', 'date'],
            'administrative_status' => ['sometimes', 'string', 'in:PRE_REGISTERED,ACTIVE,INACTIVE,DISCHARGED,ARCHIVED'],
            'emergency_contact_name' => ['nullable', 'string', 'max:160'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:25'],
            'administrative_notes' => ['nullable', 'string'],
            'registered_by' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Provide custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'person_id.unique' => 'This person is already registered as a patient.',
            'record_number.unique' => 'The record number is already in use.',
        ];
    }
}
