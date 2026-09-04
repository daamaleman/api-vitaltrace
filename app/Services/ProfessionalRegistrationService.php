<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ProfessionalRegistrationConflict;
use App\Models\HealthStaff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProfessionalRegistrationService
{
    /** @var array<int, string> */
    private const TYPES = ['NURSE', 'DOCTOR'];

    public function register(User $user, array $data, User $actor): HealthStaff
    {
        return DB::transaction(function () use ($user, $data, $actor): HealthStaff {
            $this->ensurePerson($user);

            $staff = HealthStaff::withTrashed()
                ->where('person_id', $user->person_id)
                ->lockForUpdate()
                ->first();

            if ($staff?->trashed()) {
                throw new ProfessionalRegistrationConflict(
                    'Esta persona tiene un perfil profesional eliminado. Debe restaurarse explícitamente antes de registrarla de nuevo.',
                );
            }

            $type = (string) $data['professional_type'];
            if ($staff !== null && $staff->professional_type !== $type) {
                throw new ProfessionalRegistrationConflict(
                    'La persona ya está registrada con otro tipo de profesional. El cambio DOCTOR/NURSE requiere un flujo explícito.',
                );
            }

            $attributes = [
                'person_id' => $user->person_id,
                'professional_type' => $type,
                'professional_code' => $data['professional_code'],
                'specialty_id' => $data['specialty_id'] ?? null,
                'active' => $data['active'] ?? true,
            ];

            if ($staff === null) {
                $staff = HealthStaff::create($attributes + ['created_by' => $actor->id]);
            } else {
                $staff->update($attributes + ['updated_by' => $actor->id]);
            }

            $this->syncClinicalRole($user, $type, $actor->id);

            return $staff->fresh();
        });
    }

    public function update(HealthStaff $staff, array $data, User $actor): HealthStaff
    {
        return DB::transaction(function () use ($staff, $data, $actor): HealthStaff {
            if ($staff->trashed()) {
                throw new ProfessionalRegistrationConflict('No se puede actualizar un perfil profesional eliminado.');
            }

            if (array_key_exists('professional_type', $data)) {
                throw new ProfessionalRegistrationConflict('El tipo profesional no puede cambiarse silenciosamente.');
            }

            $staff->update($data + ['updated_by' => $actor->id]);

            $user = User::query()->where('person_id', $staff->person_id)->first();
            if ($user !== null) {
                $this->syncClinicalRole($user, $staff->professional_type, $actor->id);
            }

            return $staff->fresh();
        });
    }

    public function assertRoleAssignmentAllowed(User $user, string $roleName): void
    {
        if (! in_array($roleName, self::TYPES, true)) {
            return;
        }

        $this->ensurePerson($user);

        $compatible = HealthStaff::query()
            ->where('person_id', $user->person_id)
            ->where('professional_type', $roleName)
            ->where('active', true)
            ->exists();

        if (! $compatible) {
            throw ValidationException::withMessages([
                'role_id' => ['Debes registrar primero los datos profesionales de esta persona.'],
            ]);
        }
    }

    private function ensurePerson(User $user): void
    {
        if ($user->person_id === null || $user->person === null) {
            throw ValidationException::withMessages([
                'user_id' => ['La cuenta debe estar vinculada a una persona válida.'],
            ]);
        }
    }

    private function syncClinicalRole(User $user, string $type, int $actorId): void
    {
        $role = Role::query()->where('name', $type)->first();
        if ($role === null) {
            throw (new ModelNotFoundException())->setModel(Role::class, [$type]);
        }

        $pivot = DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->lockForUpdate()
            ->first();

        $attributes = [
            'active' => true,
            'assigned_at' => now(),
            'revoked_at' => null,
            'assigned_by' => $actorId,
        ];

        if ($pivot === null) {
            DB::table('user_role')->insert($attributes + ['user_id' => $user->id, 'role_id' => $role->id]);
        } else {
            DB::table('user_role')
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->update($attributes);
        }
    }
}
