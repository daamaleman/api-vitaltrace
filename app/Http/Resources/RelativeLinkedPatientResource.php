<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RelativeLinkedPatientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $patient = $this->patient;
        $person = $patient?->person;

        return [
            'id' => $patient?->id,
            'full_name' => $person === null ? null : collect([
                $person->first_name,
                $person->middle_name,
                $person->first_last_name,
                $person->second_last_name,
            ])->filter()->implode(' '),
            'relationship' => $this->relationship,
            'status' => $this->status,
            'avatar_url' => null,
        ];
    }
}