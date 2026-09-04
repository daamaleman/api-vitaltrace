<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProfessionalRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $staff = $this->resource['health_staff'];

        return [
            'user_id' => $user->id,
            'person_id' => $user->person_id,
            'health_staff_id' => $staff->id,
            'full_name' => trim(implode(' ', array_filter([
                $staff->person?->first_name,
                $staff->person?->middle_name,
                $staff->person?->first_last_name,
                $staff->person?->second_last_name,
            ]))),
            'professional_type' => $staff->professional_type,
            'professional_code' => $staff->professional_code,
            'specialty' => $staff->specialty?->name,
            'active' => $staff->active,
            'roles' => $user->roles->pluck('name')->values(),
        ];
    }
}
