<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Models\HealthStaff;
use App\Models\Patient;
use App\Models\ProfessionalAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

final class NursePatientAccessService
{
    public function resolveNurse(User $user): HealthStaff
    {
        if (! $user->hasRole('NURSE') || $user->person_id === null) {
            $this->deny('No tienes autorización para acceder al portal de enfermería.');
        }

        $staff = HealthStaff::query()
            ->where('person_id', $user->person_id)
            ->where('professional_type', 'NURSE')
            ->where('active', true)
            ->first();

        if ($staff === null) {
            $this->deny('No tienes autorización para acceder al portal de enfermería.');
        }

        return $staff;
    }

    public function assignedPatientIds(User $user): Builder
    {
        $staff = $this->resolveNurse($user);

        return ProfessionalAssignment::query()
            ->select('patient_id')
            ->where('health_staff_id', $staff->id)
            ->where('status', 'ACTIVE')
            ->whereDate('start_date', '<=', today())
            ->where(function (Builder $query): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            });
    }

    public function assignedPatientsQuery(User $user): Builder
    {
        return Patient::query()->whereIn('id', $this->assignedPatientIds($user));
    }

    public function assertPatientAccess(User $user, Patient|int $patient): Patient
    {
        $patientId = $patient instanceof Patient ? $patient->id : $patient;

        if (! $this->assignedPatientIds($user)->where('patient_id', $patientId)->exists()) {
            $this->deny('No tienes autorización para ver la información de este paciente.');
        }

        return $patient instanceof Patient ? $patient : Patient::query()->findOrFail($patientId);
    }

    private function deny(string $message): never
    {
        throw new HttpResponseException(response()->json([
            'data' => null,
            'message' => $message,
            'errors' => null,
        ], Response::HTTP_FORBIDDEN));
    }
}
