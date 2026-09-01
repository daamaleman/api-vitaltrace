<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RelativePatientProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $person = $this->person;
        $user = $person?->user;
        $fullName = $person === null ? null : collect([
            $person->first_name,
            $person->middle_name,
            $person->first_last_name,
            $person->second_last_name,
        ])->filter()->implode(' ');

        return [
            'user_id' => $user?->id,
            'patient_id' => $this->id,
            'record_number' => $this->record_number,
            'full_name' => $fullName !== '' ? $fullName : null,
            'email' => $user?->email,
            'phone' => $person?->phone,
            'date_of_birth' => $person?->date_of_birth?->format('Y-m-d'),
            'age' => $person?->date_of_birth?->age,
            'gender' => $person?->gender,
            'address' => $person?->address,
            'identification_number' => $person?->identity_document,
            'emergency_contact' => null,
            'account_status' => $user?->status,
            'administrative_status' => $this->administrative_status,
        ];
    }
}