<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the permission resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Permission identifier.
            'id' => $this->id,
            // Permission code used by the application.
            'code' => $this->code,
            // Display name of the permission.
            'name' => $this->name,
            // Additional details about the permission.
            'description' => $this->description,
            // Creation date and time in a readable format.
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            // Reference to the creator.
            'created_by' => $this->created_by,
            // Last update date and time in a readable format.
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            // Reference to the last updater.
            'updated_by' => $this->updated_by,
        ];
    }
}
