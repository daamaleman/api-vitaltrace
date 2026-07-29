<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientTreatmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $diagnosis = $this->whenLoaded('diagnosis');
        $prescriber = $this->whenLoaded('prescribedBy');
        $person = $prescriber?->relationLoaded('person') ? $prescriber->person : null;
        $healthStaff = $prescriber?->relationLoaded('healthStaff') ? $prescriber->healthStaff : null;
        $specialty = $healthStaff?->relationLoaded('specialty') ? $healthStaff->specialty : null;

        return [
            'id' => $this->id,
            'diagnosis_id' => $this->diagnosis_id,
            'diagnosis' => $diagnosis === null ? null : [
                'id' => $diagnosis->id,
                'cie_code' => $diagnosis->cie_code,
                'description' => $diagnosis->description,
                'diagnosis_date' => $diagnosis->diagnosis_date?->format('Y-m-d'),
                'status' => $diagnosis->status,
            ],
            'indications' => $this->indications,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'prescribed_by' => $this->prescribed_by,
            'prescriber' => $prescriber === null ? null : [
                'id' => $prescriber->id,
                'professional_type' => $healthStaff?->professional_type,
                'full_name' => $person === null ? null : collect([
                    $person->first_name,
                    $person->middle_name,
                    $person->first_last_name,
                    $person->second_last_name,
                ])->filter()->implode(' '),
                'specialty' => $specialty === null ? null : [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                ],
            ],
        ];
    }
}
