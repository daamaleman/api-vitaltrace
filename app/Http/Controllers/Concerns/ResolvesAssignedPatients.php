<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\HealthStaff;
use App\Models\ProfessionalAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Restricts clinical data to the patients actively assigned to the
 * authenticated professional (RN-06). Reused by clinical controllers so the
 * scoping rule lives in one place.
 */
trait ResolvesAssignedPatients
{
    /**
     * The health_staff record linked to the current user, or null.
     */
    protected function resolveStaff(Request $request): ?HealthStaff
    {
        return HealthStaff::where('person_id', $request->user()->person_id)->first();
    }

    /**
     * IDs of patients actively assigned to the current professional.
     * Returns an empty collection when there is no linked staff record.
     */
    protected function assignedPatientIds(Request $request): Collection
    {
        $staff = $this->resolveStaff($request);

        if ($staff === null) {
            return collect();
        }

        return ProfessionalAssignment::query()
            ->where('health_staff_id', $staff->id)
            ->where('status', 'ACTIVE')
            ->pluck('patient_id');
    }
}
