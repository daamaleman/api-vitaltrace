<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientNotificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->subject,
            'message' => $this->general_message,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->utc()->toIso8601String(),
            'related_type' => $this->related_type,
            'related_id' => $this->related_id,
            'action_route' => $this->action_route,
            'created_at' => $this->created_at?->utc()->toIso8601String(),
        ];
    }
}
