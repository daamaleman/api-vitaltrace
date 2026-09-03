<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NurseDiagnosisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cie_code' => $this->cie_code,
            'description' => $this->description,
            'diagnosis_date' => $this->diagnosis_date?->format('Y-m-d'),
            'status' => $this->status,
        ];
    }
}
