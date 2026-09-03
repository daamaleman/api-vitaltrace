<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NurseMeasurementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'measurement_type' => $this->measurementType === null ? null : [
                'id' => $this->measurementType->id,
                'name' => $this->measurementType->name,
                'base_unit' => $this->measurementType->base_unit,
                'decimals' => $this->measurementType->decimals,
            ],
            'value' => $this->value,
            'unit' => $this->unit,
            'measured_at' => $this->measured_at?->format('Y-m-d H:i:s'),
            'origin' => $this->origin,
            'observation' => $this->observation,
            'review_status' => $this->review_status,
        ];
    }
}
