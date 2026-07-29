<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'email' => $this->email,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'last_access_at' => $this->last_access_at?->format('Y-m-d H:i:s'),
            'failed_attempts' => $this->failed_attempts,
            'blocked_until' => $this->blocked_until?->format('Y-m-d H:i:s'),
            'person' => new PersonResource($this->whenLoaded('person')),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ]);
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'updated_by' => $this->updated_by,
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            'deleted_by' => $this->deleted_by,
        ];
    }
}