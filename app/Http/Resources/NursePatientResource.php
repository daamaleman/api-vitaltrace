<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NursePatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $person = $this->person;

        return [
            'patient_id' => $this->id,
            'record_number' => $this->record_number,
            'full_name' => collect([$person?->first_name, $person?->middle_name, $person?->first_last_name, $person?->second_last_name])->filter()->implode(' '),
            'birth_date' => $person?->date_of_birth?->format('Y-m-d'),
            'age' => $person?->date_of_birth?->age,
            'sex' => $person?->gender,
            'administrative_status' => $this->administrative_status,
            'active_alerts_count' => $this->when(isset($this->active_alerts_count), (int) ($this->active_alerts_count ?? 0)),
            'critical_alerts_count' => $this->when(isset($this->critical_alerts_count), (int) ($this->critical_alerts_count ?? 0)),
            'last_measurement' => $this->whenLoaded('latestMeasurement', fn () => $this->latestMeasurement === null ? null : new NurseMeasurementResource($this->latestMeasurement)),
            'next_appointment' => $this->whenLoaded('nextAppointment', fn () => $this->nextAppointment === null ? null : new NurseAppointmentResource($this->nextAppointment)),
        ];
    }
}
