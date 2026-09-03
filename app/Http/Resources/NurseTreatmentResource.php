<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NurseTreatmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'indication' => $this->indications,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'diagnosis' => $this->diagnosis === null ? null : new NurseDiagnosisResource($this->diagnosis),
            'medications' => $this->whenLoaded('treatmentMedications', fn () => $this->treatmentMedications->map(fn ($item) => [
                'id' => $item->medication?->id,
                'name' => $item->medication?->name,
                'dose' => $item->dose,
                'route' => $item->route,
                'frequency' => $item->frequency,
                'schedules' => $item->schedules,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
            ])->values()),
        ];
    }
}
