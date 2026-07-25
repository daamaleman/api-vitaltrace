<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * RelativeResource
 *
 * This resource class transforms a Relative model into a JSON response.
 * It handles the serialization of relative data with timestamps and audit fields.
 */
class RelativeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Converts the Relative model instance into an associative array containing:
     * - Basic fields: id, person_id
     * - Relationships: person (loaded via eager loading)
     * - Timestamps: created_at, updated_at, deleted_at (formatted as Y-m-d H:i:s)
     * - Audit fields: created_by, updated_by, deleted_by
     *
     * @param Request $request The HTTP request instance
     * @return array<string, mixed> The transformed resource array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'person' => new PersonResource($this->whenLoaded('person')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'updated_by' => $this->updated_by,
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            'deleted_by' => $this->deleted_by,
        ];
    }
}
