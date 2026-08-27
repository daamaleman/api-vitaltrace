<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Read-only clinical history projection for the authenticated patient.
 */
class PatientClinicalHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'record_number' => $this->resource['patient']->record_number,
            'diagnoses' => $this->resource['diagnoses']->map(fn ($diagnosis) => [
                'id' => $diagnosis->id,
                'cie_code' => $diagnosis->cie_code,
                'description' => $diagnosis->description,
                'diagnosis_date' => $diagnosis->diagnosis_date?->format('Y-m-d'),
                'status' => $diagnosis->status,
                'professional' => $this->professional($diagnosis->registeredBy),
            ])->values(),
            'clinical_evolutions' => $this->resource['evolutions']->map(fn ($evolution) => [
                'id' => $evolution->id,
                'clinical_summary' => $evolution->clinical_summary,
                'status' => $evolution->status,
                'recorded_at' => $evolution->recorded_at?->format('Y-m-d H:i:s'),
                'professional' => $this->professional($evolution->registeredBy),
            ])->values(),
            'current_treatments' => $this->resource['treatments']->map(fn ($treatment) => [
                'id' => $treatment->id,
                'diagnosis_id' => $treatment->diagnosis_id,
                'indications' => $treatment->indications,
                'start_date' => $treatment->start_date?->format('Y-m-d'),
                'end_date' => $treatment->end_date?->format('Y-m-d'),
                'status' => $treatment->status,
                'professional' => $this->professional($treatment->prescribedBy),
            ])->values(),
            'recent_measurements' => $this->resource['measurements']->map(fn ($measurement) => [
                'id' => $measurement->id,
                'value' => $measurement->value,
                'unit' => $measurement->unit,
                'measured_at' => $measurement->measured_at?->format('Y-m-d H:i:s'),
                'observation' => $measurement->observation,
                'measurement_type' => $measurement->measurementType === null ? null : [
                    'id' => $measurement->measurementType->id,
                    'name' => $measurement->measurementType->name,
                    'code' => $measurement->measurementType->code,
                ],
            ])->values(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function professional(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $person = $user->relationLoaded('person') ? $user->person : null;
        $healthStaff = $user->relationLoaded('healthStaff') ? $user->healthStaff : null;
        $specialty = $healthStaff?->relationLoaded('specialty')
            ? $healthStaff->specialty
            : null;

        return [
            'full_name' => $person === null ? null : collect([
                $person->first_name,
                $person->middle_name,
                $person->first_last_name,
                $person->second_last_name,
            ])->filter()->implode(' '),
            'professional_type' => $healthStaff?->professional_type,
            'specialty' => $specialty?->name,
        ];
    }
}
