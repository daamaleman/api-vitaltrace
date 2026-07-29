<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $person = $this->whenLoaded('person');

        return [
            'id' => $this->id,
            'record_number' => $this->record_number,
            'administrative_status' => $this->administrative_status,
            'full_name' => $person === null ? null : collect([
                $person->first_name,
                $person->middle_name,
                $person->first_last_name,
                $person->second_last_name,
            ])->filter()->implode(' '),
        ];
    }
}
