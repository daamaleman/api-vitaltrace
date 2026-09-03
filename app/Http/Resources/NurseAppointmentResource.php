<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NurseAppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $staff = $this->whenLoaded('healthStaff');
        $person = $staff?->person;

        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->duration_minutes,
            'reason' => $this->reason,
            'status' => $this->status,
            'professional' => $staff === null ? null : [
                'id' => $staff->id,
                'full_name' => collect([$person?->first_name, $person?->middle_name, $person?->first_last_name, $person?->second_last_name])->filter()->implode(' '),
                'professional_type' => $staff->professional_type,
                'specialty' => $staff->specialty?->name,
            ],
        ];
    }
}
