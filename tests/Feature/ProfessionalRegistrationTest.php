<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HealthStaff;
use App\Models\Patient;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ProfessionalRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ProfessionalRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['SYSTEM_ADMIN', 'ADMISSION', 'PATIENT', 'NURSE', 'DOCTOR', 'RELATIVE'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName], ['active' => true]);
        }
    }

    public function test_patient_can_be_registered_as_nurse_without_losing_patient_role(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $this->patientFor($user);

        $this->register($admin, $user, 'NURSE', 'RN-TEST-001')
            ->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.person_id', $user->person_id)
            ->assertJsonPath('data.professional_type', 'NURSE')
            ->assertJsonPath('data.professional_code', 'RN-TEST-001')
            ->assertJsonPath('data.roles', ['PATIENT', 'NURSE']);

        $this->assertDatabaseHas('health_staff', [
            'person_id' => $user->person_id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-TEST-001',
        ]);
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => Role::where('name', 'NURSE')->firstOrFail()->id,
            'active' => true,
        ]);
        $this->assertNotNull($user->patient()->first());
    }

    public function test_patient_can_be_registered_as_doctor(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $this->patientFor($user);

        $this->register($admin, $user, 'DOCTOR', 'MD-TEST-001')
            ->assertCreated()
            ->assertJsonPath('data.professional_type', 'DOCTOR');

        $this->assertDatabaseHas('health_staff', [
            'person_id' => $user->person_id,
            'professional_type' => 'DOCTOR',
        ]);
    }

    public function test_compatible_registration_updates_without_duplicate_profile(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');

        $this->register($admin, $user, 'NURSE', 'RN-TEST-002')->assertCreated();
        $this->register($admin, $user, 'NURSE', 'RN-TEST-003')->assertCreated();

        $this->assertSame(1, HealthStaff::where('person_id', $user->person_id)->count());
        $this->assertDatabaseHas('health_staff', [
            'person_id' => $user->person_id,
            'professional_code' => 'RN-TEST-003',
        ]);
    }

    public function test_incompatible_existing_profile_returns_conflict(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        HealthStaff::create([
            'person_id' => $user->person_id,
            'professional_type' => 'DOCTOR',
            'professional_code' => 'MD-TEST-002',
            'active' => true,
        ]);

        $this->register($admin, $user, 'NURSE', 'RN-TEST-004')
            ->assertStatus(409)
            ->assertJsonPath('errors.professional.0', 'La persona ya está registrada con otro tipo de profesional. El cambio DOCTOR/NURSE requiere un flujo explícito.');
    }

    public function test_duplicate_code_and_unknown_specialty_are_validation_errors(): void
    {
        [$admin, $first] = $this->adminAndUserWithRole('PATIENT');
        [, $second] = $this->adminAndUserWithRole('PATIENT');

        HealthStaff::create([
            'person_id' => $first->person_id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-DUPLICATE',
            'active' => true,
        ]);

        $this->register($admin, $second, 'NURSE', 'RN-DUPLICATE')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('professional_code');

        $this->register($admin, $second, 'NURSE', 'RN-TEST-005', 999999)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('specialty_id');
    }

    public function test_non_admin_and_direct_clinical_roles_without_profile_are_blocked(): void
    {
        [, $patient] = $this->adminAndUserWithRole('PATIENT');
        [$admin, $target] = $this->adminAndUserWithRole('PATIENT');

        $this->actingAs($patient)->postJson('/api/v1/admin/professionals/register', [
            'user_id' => $target->id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-TEST-006',
        ])->assertForbidden();

        foreach (['NURSE', 'DOCTOR'] as $roleName) {
            $role = Role::where('name', $roleName)->firstOrFail();
            $this->actingAs($admin)->postJson("/api/v1/admin/users/{$target->id}/roles", [
                'role_id' => $role->id,
            ])->assertUnprocessable()->assertJsonValidationErrors('role_id');
        }
    }

    public function test_registered_nurse_is_listed_and_accepts_nurse_assignment(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $patient = $this->newPatient();
        $this->register($admin, $user, 'NURSE', 'RN-TEST-007');

        $staff = HealthStaff::where('person_id', $user->person_id)->firstOrFail();

        $this->actingAs($admin)->getJson('/api/v1/admission/staff')
            ->assertOk()
            ->assertJsonFragment(['id' => $staff->id]);

        $this->actingAs($admin)->postJson("/api/v1/admission/patients/{$patient->id}/assignments", [
            'health_staff_id' => $staff->id,
            'assignment_type' => 'NURSE',
        ])->assertCreated();
    }

    public function test_incompatible_assignment_types_are_rejected(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $patient = $this->newPatient();
        $nurse = HealthStaff::create([
            'person_id' => $user->person_id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-TEST-008',
            'active' => true,
        ]);

        $this->actingAs($admin)->postJson("/api/v1/admission/patients/{$patient->id}/assignments", [
            'health_staff_id' => $nurse->id,
            'assignment_type' => 'PRIMARY_DOCTOR',
        ])->assertUnprocessable()->assertJsonValidationErrors('assignment_type');
    }

    public function test_doctor_primary_assignment_is_allowed_and_inactive_staff_is_hidden(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $patient = $this->newPatient();
        $doctor = HealthStaff::create([
            'person_id' => $user->person_id,
            'professional_type' => 'DOCTOR',
            'professional_code' => 'MD-TEST-003',
            'active' => true,
        ]);

        $this->actingAs($admin)->postJson("/api/v1/admission/patients/{$patient->id}/assignments", [
            'health_staff_id' => $doctor->id,
            'assignment_type' => 'PRIMARY_DOCTOR',
        ])->assertCreated();

        $inactive = HealthStaff::create([
            'person_id' => $this->newPerson()->id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-TEST-009',
            'active' => false,
        ]);

        $this->actingAs($admin)->getJson('/api/v1/admission/staff')
            ->assertOk()
            ->assertJsonMissing(['id' => $inactive->id]);
    }

    public function test_soft_deleted_profile_conflicts_and_transaction_rolls_back_missing_role(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $staff = HealthStaff::create([
            'person_id' => $user->person_id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-TEST-010',
            'active' => true,
        ]);
        $staff->delete();

        $this->register($admin, $user, 'NURSE', 'RN-TEST-011')->assertStatus(409);

        [$secondAdmin, $secondUser] = $this->adminAndUserWithRole('PATIENT');
        Role::where('name', 'NURSE')->delete();

        $this->register($secondAdmin, $secondUser, 'NURSE', 'RN-TEST-012')->assertNotFound();
        $this->assertDatabaseMissing('health_staff', [
            'person_id' => $secondUser->person_id,
            'professional_code' => 'RN-TEST-012',
        ]);
    }

    public function test_professional_type_is_immutable_and_role_reactivation_is_guarded(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $staff = HealthStaff::create([
            'person_id' => $user->person_id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-TEST-013',
            'active' => true,
        ]);
        $role = Role::where('name', 'NURSE')->firstOrFail();
        $pivot = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'active' => false,
        ]);

        $this->actingAs($admin)->putJson("/api/v1/health-staff/{$staff->id}", [
            'professional_type' => 'DOCTOR',
        ])->assertUnprocessable()->assertJsonValidationErrors('professional_type');

        $staff->update(['active' => false]);
        $this->actingAs($admin)->putJson("/api/v1/user-roles/{$pivot->id}", [
            'active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('role_id');
    }

    public function test_invalid_type_and_missing_code_are_validation_errors(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');

        $this->actingAs($admin)->postJson('/api/v1/admin/professionals/register', [
            'user_id' => $user->id,
            'professional_type' => 'THERAPIST',
            'professional_code' => 'BAD-001',
        ])->assertUnprocessable()->assertJsonValidationErrors('professional_type');

        $this->actingAs($admin)->postJson('/api/v1/admin/professionals/register', [
            'user_id' => $user->id,
            'professional_type' => 'NURSE',
        ])->assertUnprocessable()->assertJsonValidationErrors('professional_code');
    }

    public function test_registration_reactivates_revoked_clinical_role(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $role = Role::where('name', 'NURSE')->firstOrFail();
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'active' => false,
            'revoked_at' => now(),
        ]);

        $this->register($admin, $user, 'NURSE', 'RN-TEST-014')->assertCreated();

        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'active' => true,
        ]);
    }

    public function test_inactive_registered_professional_is_hidden_but_registration_is_atomic(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');

        $this->register($admin, $user, 'NURSE', 'RN-TEST-015', null, false)->assertCreated();

        $this->actingAs($admin)->getJson('/api/v1/admission/staff')
            ->assertOk()
            ->assertJsonMissing(['professional_code' => 'RN-TEST-015']);
        $this->assertDatabaseHas('health_staff', [
            'person_id' => $user->person_id,
            'professional_code' => 'RN-TEST-015',
            'active' => false,
        ]);
    }

    public function test_update_changes_code_specialty_and_active_but_not_type(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $staff = HealthStaff::create([
            'person_id' => $user->person_id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-TEST-016',
            'active' => true,
        ]);
        $specialty = \App\Models\Specialty::create([
            'name' => 'Critical Care Test',
            'active' => true,
        ]);

        $this->actingAs($admin)->putJson("/api/v1/health-staff/{$staff->id}", [
            'professional_code' => 'RN-TEST-017',
            'specialty_id' => $specialty->id,
            'active' => false,
        ])->assertOk();

        $this->assertDatabaseHas('health_staff', [
            'id' => $staff->id,
            'professional_code' => 'RN-TEST-017',
            'specialty_id' => $specialty->id,
            'active' => false,
        ]);
    }

    public function test_generic_role_store_blocks_clinical_role_without_profile(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $role = Role::where('name', 'NURSE')->firstOrFail();

        $this->actingAs($admin)->postJson('/api/v1/user-roles', [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('role_id');
    }

    public function test_non_clinical_role_assignment_still_works(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $role = Role::where('name', 'RELATIVE')->firstOrFail();

        $this->actingAs($admin)->postJson('/api/v1/user-roles', [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'active' => true,
        ])->assertCreated();
    }

    public function test_doctor_can_receive_secondary_doctor_assignment(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $patient = $this->newPatient();
        $doctor = HealthStaff::create([
            'person_id' => $user->person_id,
            'professional_type' => 'DOCTOR',
            'professional_code' => 'MD-TEST-004',
            'active' => true,
        ]);

        $this->actingAs($admin)->postJson("/api/v1/admission/patients/{$patient->id}/assignments", [
            'health_staff_id' => $doctor->id,
            'assignment_type' => 'SECONDARY_DOCTOR',
        ])->assertCreated();
    }

    public function test_doctor_cannot_receive_nurse_assignment(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $patient = $this->newPatient();
        $doctor = HealthStaff::create([
            'person_id' => $user->person_id,
            'professional_type' => 'DOCTOR',
            'professional_code' => 'MD-TEST-005',
            'active' => true,
        ]);

        $this->actingAs($admin)->postJson("/api/v1/admission/patients/{$patient->id}/assignments", [
            'health_staff_id' => $doctor->id,
            'assignment_type' => 'NURSE',
        ])->assertUnprocessable()->assertJsonValidationErrors('assignment_type');
    }

    public function test_service_rejects_user_without_person(): void
    {
        $user = User::make(['person_id' => null]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ProfessionalRegistrationService::class)->assertRoleAssignmentAllowed($user, 'NURSE');
    }
    public function test_health_staff_resource_store_uses_registration_service(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');

        $this->actingAs($admin)->postJson('/api/v1/health-staff', [
            'user_id' => $user->id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-TEST-018',
        ])->assertCreated();

        $this->assertDatabaseHas('health_staff', [
            'person_id' => $user->person_id,
            'professional_code' => 'RN-TEST-018',
        ]);
    }

    public function test_update_rejects_changing_person_identity(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $staff = HealthStaff::create([
            'person_id' => $user->person_id,
            'professional_type' => 'NURSE',
            'professional_code' => 'RN-TEST-019',
            'active' => true,
        ]);

        $this->actingAs($admin)->putJson("/api/v1/health-staff/{$staff->id}", [
            'person_id' => $this->newPerson()->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('person_id');
    }

    public function test_active_registration_reactivates_an_existing_revoked_role(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $role = Role::where('name', 'DOCTOR')->firstOrFail();
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'active' => false,
            'revoked_at' => now(),
        ]);

        $this->register($admin, $user, 'DOCTOR', 'MD-TEST-006')->assertCreated();

        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'active' => true,
        ]);
    }

    public function test_registering_with_existing_specialty_returns_safe_projection(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $specialty = \App\Models\Specialty::create([
            'name' => 'Emergency Test',
            'active' => true,
        ]);

        $this->register($admin, $user, 'NURSE', 'RN-TEST-020', $specialty->id)
            ->assertCreated()
            ->assertJsonPath('data.specialty', 'Emergency Test')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.tokens');
    }

    public function test_user_role_update_keeps_non_clinical_role_compatible(): void
    {
        [$admin, $user] = $this->adminAndUserWithRole('PATIENT');
        $role = Role::where('name', 'RELATIVE')->firstOrFail();
        $pivot = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'active' => false,
            'revoked_at' => now(),
        ]);

        $this->actingAs($admin)->putJson("/api/v1/user-roles/{$pivot->id}", [
            'active' => true,
        ])->assertOk();
    }
    private function register(User $admin, User $user, string $type, string $code, ?int $specialtyId = null, ?bool $active = null)
    {
        return $this->actingAs($admin)->postJson('/api/v1/admin/professionals/register', array_filter([
            'user_id' => $user->id,
            'professional_type' => $type,
            'professional_code' => $code,
            'specialty_id' => $specialtyId,
            'active' => $active,
            'active' => $active,
        ], static fn ($value) => $value !== null));
    }

    private function newPerson(): Person
    {
        return Person::create([
            'first_name' => fake()->firstName(),
            'first_last_name' => fake()->lastName(),
            'date_of_birth' => '1990-01-01',
            'gender' => 'UNSPECIFIED',
        ]);
    }

    /** @return array{User, User} */
    private function adminAndUserWithRole(string $roleName): array
    {
        $admin = $this->newUser('SYSTEM_ADMIN');
        $admissionRole = Role::where('name', 'ADMISSION')->firstOrFail();
        UserRole::create([
            'user_id' => $admin->id,
            'role_id' => $admissionRole->id,
            'active' => true,
            'assigned_at' => now(),
        ]);

        return [$admin, $this->newUser($roleName)];
    }

    private function newUser(string $roleName): User
    {
        $user = User::create([
            'person_id' => $this->newPerson()->id,
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password#1',
            'status' => 'ACTIVE',
        ]);
        $role = Role::firstOrCreate(['name' => $roleName], ['active' => true]);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'active' => true,
            'assigned_at' => now(),
        ]);

        return $user;
    }

    private function patientFor(User $user): Patient
    {
        return Patient::create([
            'person_id' => $user->person_id,
            'record_number' => fake()->unique()->bothify('VT-TEST-###'),
            'admission_date' => '2026-01-01',
            'administrative_status' => 'ACTIVE',
        ]);
    }

    private function newPatient(): Patient
    {
        return Patient::create([
            'person_id' => $this->newPerson()->id,
            'record_number' => fake()->unique()->bothify('VT-TEST-###'),
            'admission_date' => '2026-01-01',
            'administrative_status' => 'ACTIVE',
        ]);
    }
}
