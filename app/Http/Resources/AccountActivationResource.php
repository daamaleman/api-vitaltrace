<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API representation of an account activation record.
 *
 * The code hash is intentionally omitted; it must never leave the backend.
 */
class AccountActivationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'sent_to_email' => $this->sent_to_email,
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'used_at' => $this->used_at?->format('Y-m-d H:i:s'),
            'attempts' => $this->attempts,
            'status' => $this->status,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_by' => $this->created_by,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'updated_by' => $this->updated_by,
        ];
    }
}
