<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
{
    /**
     * Transform the user role resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'active' => $this->active,
            'assigned_at' => $this->assigned_at?->format('Y-m-d H:i:s'),
            'revoked_at' => $this->revoked_at?->format('Y-m-d H:i:s'),
            'assigned_by' => $this->assigned_by,
            'user' => new UserResource($this->whenLoaded('user')),
            'role' => new RoleResource($this->whenLoaded('role')),
        ];
    }
}
