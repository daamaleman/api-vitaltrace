<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API representation of a measurement.
 */
class MeasurementResource extends JsonResource
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
            'value' => $this->value,
            'unit' => $this->unit,
            'measured_at' => $this->measured_at?->format('Y-m-d H:i:s'),
            'origin' => $this->origin,
            'author_user_id' => $this->author_user_id,
            'observation' => $this->observation,
            'review_status' => $this->review_status,
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i:s'),
            'reviewed_by' => $this->reviewed_by,
            'review_observation' => $this->review_observation,
            'reviewer' => $this->relationLoaded('reviewer') && $this->reviewer !== null ? [
                'id' => $this->reviewer->id,
                'full_name' => collect([
                    $this->reviewer->person?->first_name,
                    $this->reviewer->person?->middle_name,
                    $this->reviewer->person?->first_last_name,
                    $this->reviewer->person?->second_last_name,
                ])->filter()->implode(' '),
            ] : null,
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
