<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NurseAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'measurement_id' => $this->measurement_id,
            'type' => $this->type,
            'severity' => $this->severity,
            'status' => $this->status,
            'description' => $this->description,
            'generated_at' => $this->generated_at?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),
            'history' => $this->whenLoaded('history', fn () => $this->history->map(fn ($entry) => [
                'id' => $entry->id,
                'action' => $entry->action,
                'previous_status' => $entry->previous_status,
                'new_status' => $entry->new_status,
                'comment' => $entry->comment,
                'created_at' => $entry->created_at?->format('Y-m-d H:i:s'),
            ])->values()),
        ];
    }
}
