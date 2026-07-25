<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a RolePermission model into a JSON-friendly array.
 */
class RolePermissionResource extends JsonResource
{
    /**
     * Convert the resource into an array.
     *
     * Includes base relationship identifiers, optionally loaded
     * related resources, and audit timestamps/users.
     *
     * @param Request $request The current HTTP request instance.
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role_id' => $this->role_id,
            'permission_id' => $this->permission_id,
            'role' => new RoleResource($this->whenLoaded('role')),
            'permission' => new PermissionResource($this->whenLoaded('permission')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'updated_by' => $this->updated_by,
        ];
    }
}
