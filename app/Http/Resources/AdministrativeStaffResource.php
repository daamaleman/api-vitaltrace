<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdministrativeStaffResource extends JsonResource
{
    /**
     * AdministrativeStaffResource
     *
     * Transforms an AdministrativeStaff model into an array suitable for JSON
     * responses. This resource exposes common staff fields and related
     * person information when loaded.
     *
     * Example output keys:
     * - id, person_id, employee_code, type, position, active
     * - person (nested PersonResource)
     * - created_at, created_by, updated_at, updated_by, deleted_at, deleted_by
     */
    /**
     * Convert the resource into an array.
     *
     * This method maps model attributes to a plain array and formats
     * timestamp fields to 'Y-m-d H:i:s' when present. Related person data
     * is included via the PersonResource only when the relation is loaded.
     *
     * @param Request $request Current HTTP request instance.
     * @return array<string, mixed> The array representation of the resource.
     */
    public function toArray(Request $request): array
    {
        return [
            // Primary identifiers
            'id' => $this->id,
            'person_id' => $this->person_id,

            // Employment details
            'employee_code' => $this->employee_code,
            'type' => $this->type,
            'position' => $this->position,
            'active' => $this->active,

            // Related resources
            'person' => new PersonResource($this->whenLoaded('person')),

            // Audit timestamps and user references
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'updated_by' => $this->updated_by,
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            'deleted_by' => $this->deleted_by,
        ];
    }
}
