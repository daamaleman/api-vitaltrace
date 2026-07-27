<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API representation of an alert history entry.
 */
class AlertHistoryResource extends JsonResource
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
            'alert_id' => $this->alert_id,
            'action' => $this->action,
            'previous_status' => $this->previous_status,
            'new_status' => $this->new_status,
            'comment' => $this->comment,
            'user_id' => $this->user_id,
            'alert' => new AlertResource($this->whenLoaded('alert')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
