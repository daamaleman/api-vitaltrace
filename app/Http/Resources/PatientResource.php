<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource representation of a Patient model.
 *
 * This class transforms a Patient model instance into an array suitable
 * for JSON responses. The toArray method returns a flat array of common
 * patient attributes and meta fields (timestamps and user references).
 */
class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Returned array keys:
     * - id: integer, primary key of the patient record
     * - person_id: integer, foreign key referencing the person entity
     * - record_number: string|null, hospital or internal record identifier
     * - admission_date: string|null, formatted as YYYY-MM-DD
     * - administrative_status: string|null, current administrative state
     * - emergency_contact_name: string|null, contact person name
     * - emergency_contact_phone: string|null, contact phone number
     * - administrative_notes: string|null, free text notes
     * - registered_by: integer|null, user id who registered the patient
     * - person: PersonResource|null, nested resource when relationship loaded
     * - created_at / updated_at / deleted_at: string|null, timestamps formatted as YYYY-MM-DD HH:MM:SS
     * - created_by / updated_by / deleted_by: integer|null, user ids for audit
     *
     * @param Request $request Incoming HTTP request instance (unused)
     * @return array<string, mixed> Array representation of the patient
     */
    public function toArray(Request $request): array
    {
        return [
            // Basic identifiers
            'id' => $this->id,
            'person_id' => $this->person_id,
            'record_number' => $this->record_number,

            // Clinical / administrative fields
            'admission_date' => $this->admission_date?->format('Y-m-d'),
            'administrative_status' => $this->administrative_status,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'administrative_notes' => $this->administrative_notes,
            'registered_by' => $this->registered_by,

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
