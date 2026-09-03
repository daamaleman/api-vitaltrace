<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NurseClinicalEvolutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'clinical_summary' => $this->clinical_summary,
            'status' => $this->status,
            'recorded_at' => $this->recorded_at?->format('Y-m-d H:i:s'),
        ];
    }
}
