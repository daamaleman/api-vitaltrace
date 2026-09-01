<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Models\Patient;
use App\Models\PatientRelative;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class RelativePatientAccessService
{
    public function authorizedPatients(User $user): Builder
    {
        $relative = $user->relative;

        if (! $user->hasRole('RELATIVE') || $relative === null) {
            return PatientRelative::query()->whereRaw('1 = 0');
        }

        return PatientRelative::query()
            ->with('patient.person')
            ->where('relative_id', $relative->id)
            ->where('status', 'ACTIVE')
            ->whereDate('start_date', '<=', today())
            ->where(function (Builder $query): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            });
    }

    public function resolvePatient(User $user, int $patientId): Patient
    {
        if (! $user->hasRole('RELATIVE') || $user->relative === null) {
            throw new HttpException(403, 'You are not authorized to view this patient.');
        }

        $relation = $this->authorizedPatients($user)
            ->where('patient_id', $patientId)
            ->first();

        if ($relation === null || $relation->patient === null) {
            throw new HttpException(403, 'You are not authorized to view this patient.');
        }

        return $relation->patient;
    }
}