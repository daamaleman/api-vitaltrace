<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Safe representation of a relative relationship for the patient portal.
 */
class PatientPortalRelativeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $relative = $this->resource->relationLoaded('relative')
            ? $this->relative
            : null;
        $person = $relative?->relationLoaded('person')
            ? $relative->person
            : null;
        $fullName = $person === null
            ? null
            : collect([
                $person->first_name,
                $person->middle_name,
                $person->first_last_name,
                $person->second_last_name,
            ])->filter()->implode(' ');

        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'relative_id' => $this->relative_id,
            'relationship' => $this->relationship,
            'scope' => $this->scope,
            'status' => $this->status,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'relative' => $relative === null ? null : [
                'id' => $relative->id,
                'person_id' => $relative->person_id,
                'person' => $person === null ? null : [
                    'id' => $person->id,
                    'full_name' => $fullName !== '' ? $fullName : null,
                    'phone' => $person->phone,
                ],
            ],
        ];
    }
}
