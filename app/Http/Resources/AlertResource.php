<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API representation of an alert.
 */
class AlertResource extends JsonResource
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
            'measurement_id' => $this->measurement_id,
            'type' => $this->type,
            'severity' => $this->severity,
            'status' => $this->status,
            'description' => $this->description,
            'generated_at' => $this->generated_at?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'measurement' => new MeasurementResource($this->whenLoaded('measurement')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'updated_by' => $this->updated_by,
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            'deleted_by' => $this->deleted_by,
        ];
    }
}
