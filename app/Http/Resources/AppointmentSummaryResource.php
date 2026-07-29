<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $healthStaff = $this->whenLoaded('healthStaff');
        $person = $healthStaff?->relationLoaded('person') ? $healthStaff->person : null;
        $specialty = $healthStaff?->relationLoaded('specialty') ? $healthStaff->specialty : null;

        return [
            'id' => $this->id,
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->duration_minutes,
            'reason' => $this->reason,
            'status' => $this->status,
            'professional' => $healthStaff === null ? null : [
                'id' => $healthStaff->id,
                'professional_type' => $healthStaff->professional_type,
                'full_name' => $person === null ? null : collect([
                    $person->first_name,
                    $person->middle_name,
                    $person->first_last_name,
                    $person->second_last_name,
                ])->filter()->implode(' '),
                'specialty' => $specialty === null ? null : [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                ],
            ],
        ];
    }
}
