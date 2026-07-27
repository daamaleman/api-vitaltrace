<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API representation of an integration log entry.
 */
class IntegrationLogResource extends JsonResource
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
            'service' => $this->service,
            'operation' => $this->operation,
            'local_reference' => $this->local_reference,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'error_summary' => $this->error_summary,
            'request_id' => $this->request_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
