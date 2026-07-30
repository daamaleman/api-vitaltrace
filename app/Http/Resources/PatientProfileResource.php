<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Safe account and demographic profile for the authenticated patient.
 */
class PatientProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $person = $this->resource->relationLoaded('person') ? $this->person : null;
        $patient = $this->resource->relationLoaded('patient') ? $this->patient : null;
        $fullName = $person === null
            ? null
            : collect([
                $person->first_name,
                $person->middle_name,
                $person->first_last_name,
                $person->second_last_name,
            ])->filter()->implode(' ');
        $hasEmergencyContact = $patient !== null
            && ($patient->emergency_contact_name !== null
                || $patient->emergency_contact_phone !== null);

        return [
            'user_id' => $this->id,
            'patient_id' => $patient?->id,
            'record_number' => $patient?->record_number,
            'full_name' => $fullName !== '' ? $fullName : null,
            'email' => $this->email,
            'phone' => $person?->phone,
            'date_of_birth' => $person?->date_of_birth?->format('Y-m-d'),
            'age' => $person?->date_of_birth?->age,
            'gender' => $person?->gender,
            'address' => $person?->address,
            'identification_number' => $person?->identity_document,
            'emergency_contact' => $hasEmergencyContact ? [
                'name' => $patient->emergency_contact_name,
                'phone' => $patient->emergency_contact_phone,
            ] : null,
            'account_status' => $this->status,
            'administrative_status' => $patient?->administrative_status,
        ];
    }
}
