<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API representation of a clinical range.
 */
class ClinicalRangeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'measurement_type_id' => $this->measurement_type_id,
            'min_value' => $this->min_value,
            'max_value' => $this->max_value,
            'severity' => $this->severity,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'defined_by' => $this->defined_by,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'measurement_type' => new MeasurementTypeResource($this->whenLoaded('measurementType')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'updated_by' => $this->updated_by,
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            'deleted_by' => $this->deleted_by,
        ];
    }
}
