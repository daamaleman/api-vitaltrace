<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'first_name'        => $this->first_name,
            'middle_name'       => $this->middle_name,
            'first_last_name'   => $this->first_last_name,
            'second_last_name'  => $this->second_last_name,
            // Format the date_of_birth as 'Y-m-d' if it's not null, otherwise return null
            'date_of_birth'     => $this->date_of_birth ? $this->date_of_birth->format('Y-m-d') : null,
            'gender'            => $this->gender,
            'identity_document' => $this->identity_document,
            'phone'             => $this->phone,
            'address'           => $this->address,
            // Format the created_at and updated_at timestamps as ISO 8601 strings if they're not null, otherwise return null
            'created_at'        => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'        => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
