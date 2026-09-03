<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NursePatientProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $person = $this->person;

        return [
            'id' => $this->id,
            'full_name' => collect([$person?->first_name, $person?->middle_name, $person?->first_last_name, $person?->second_last_name])->filter()->implode(' '),
            'record_number' => $this->record_number,
            'sex' => $person?->gender,
            'birth_date' => $person?->date_of_birth?->format('Y-m-d'),
            'age' => $person?->date_of_birth?->age,
            'phone' => $person?->phone,
            'emergency_contact' => [
                'name' => $this->emergency_contact_name,
                'phone' => $this->emergency_contact_phone,
            ],
            'status' => $this->administrative_status,
        ];
    }
}
