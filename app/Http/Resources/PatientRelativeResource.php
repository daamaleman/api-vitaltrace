<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for exposing a patient-relative relationship.
 *
 * Includes relationship metadata, audit fields, and optionally loaded
 * patient/relative nested resources.
 */
class PatientRelativeResource extends JsonResource
{
    /**
     * Transform the resource into an array for JSON responses.
     *
     * @param Request $request Incoming HTTP request instance.
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Primary identifiers and relationship details.
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'relative_id' => $this->relative_id,
            'relationship' => $this->relationship,
            'scope' => $this->scope,
            'status' => $this->status,

            // Relationship validity dates.
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),

            // Authorization and registration metadata.
            'registered_by' => $this->registered_by,
            'authorized_by' => $this->authorized_by,

            // Nested resources (only included when eager-loaded).
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'relative' => new RelativeResource($this->whenLoaded('relative')),

            // Audit fields.
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'updated_by' => $this->updated_by,
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            'deleted_by' => $this->deleted_by,
        ];
    }
}
