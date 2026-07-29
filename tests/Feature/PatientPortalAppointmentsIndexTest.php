<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Appointment, HealthStaff, Patient, Person, Role, Specialty, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientPortalAppointmentsIndexTest extends TestCase
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
        Carbon::setTestNow('2026-07-29 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_access_controls_and_missing_profile(): void
    {
        $this->getJson('/api/v1/patient/appointments')->assertUnauthorized();
        Sanctum::actingAs($this->user());
        $this->getJson('/api/v1/patient/appointments')->assertForbidden();

        $user = $this->user();
        $this->patientRole($user);
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/appointments')->assertNotFound()->assertExactJson([
            'data' => null,
            'message' => 'No patient profile is associated with this account.',
            'errors' => null,
        ]);
    }

    public function test_scope_order_professional_specialty_and_safe_fields(): void
    {
        [$user, $patient] = $this->patientUser();
        [, $other] = $this->patientUser();
        $specialty = Specialty::create(['name' => 'Cardiology', 'active' => true]);
        $staff = $this->staff($specialty);
        $older = $this->appointment($patient, $staff, '2026-07-28 09:00:00');
        $newer = $this->appointment($patient, $staff, '2026-07-30 09:00:00');
        $this->appointment($other, $staff, '2026-08-01 09:00:00');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/appointments')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->id)->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('data.0.professional.professional_type', 'DOCTOR')
            ->assertJsonPath('data.0.professional.full_name', 'Carlos Ruiz')
            ->assertJsonPath('data.0.professional.specialty.name', 'Cardiology')
            ->assertJsonMissingPath('data.0.professional.professional_code')
            ->assertJsonMissingPath('data.0.professional.phone')
            ->assertJsonMissingPath('data.0.professional.address');
    }

    public function test_nullable_specialty_is_supported(): void
    {
        [$user, $patient] = $this->patientUser();
        $appointment = $this->appointment($patient, $this->staff(), '2026-07-30 09:00:00');
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/appointments')->assertOk()
            ->assertJsonPath('data.0.id', $appointment->id)
            ->assertJsonPath('data.0.professional.specialty', null);
    }

    public function test_pagination_is_fifteen_and_preserves_filters(): void
    {
        [$user, $patient] = $this->patientUser();
        $staff = $this->staff();
        foreach (range(1, 16) as $day) {
            $this->appointment($patient, $staff, sprintf('2026-07-%02d 09:00:00', $day), 'CONFIRMED');
        }
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/appointments?status=CONFIRMED')->assertOk()
            ->assertJsonCount(15, 'data')->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('links.next', fn ($v) => is_string($v) && str_contains($v, 'status=CONFIRMED'));
    }

    public function test_status_filter_and_validation(): void
    {
        [$user, $patient] = $this->patientUser();
        $staff = $this->staff();
        $expected = $this->appointment($patient, $staff, '2026-07-30 09:00:00', 'ATTENDED');
        $this->appointment($patient, $staff, '2026-07-31 09:00:00', 'CANCELLED');
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/appointments?status=ATTENDED')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $expected->id);
        $this->getJson('/api/v1/patient/appointments?status=COMPLETED')->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_date_filters_and_invalid_range(): void
    {
        [$user, $patient] = $this->patientUser();
        $staff = $this->staff();
        $this->appointment($patient, $staff, '2026-07-10 09:00:00');
        $middle = $this->appointment($patient, $staff, '2026-07-20 09:00:00');
        $this->appointment($patient, $staff, '2026-07-30 09:00:00');
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/appointments?date_from=2026-07-20')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/patient/appointments?date_to=2026-07-20')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/patient/appointments?date_from=2026-07-15&date_to=2026-07-25')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $middle->id);
        $this->getJson('/api/v1/patient/appointments?date_from=2026-07-25&date_to=2026-07-20')
            ->assertUnprocessable()->assertJsonValidationErrors('date_to');
    }

    public function test_upcoming_only_returns_future_scheduled_or_confirmed(): void
    {
        [$user, $patient] = $this->patientUser();
        $staff = $this->staff();
        $this->appointment($patient, $staff, '2026-07-29 07:59:59');
        $scheduled = $this->appointment($patient, $staff, '2026-07-29 09:00:00');
        $confirmed = $this->appointment($patient, $staff, '2026-07-29 10:00:00', 'CONFIRMED');
        foreach (['ATTENDED', 'CANCELLED', 'NO_SHOW'] as $status) {
            $this->appointment($patient, $staff, '2026-07-30 09:00:00', $status);
        }
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/appointments?upcoming=true')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $confirmed->id)->assertJsonPath('data.1.id', $scheduled->id);
    }

    public function test_empty_result_is_successful_and_paginated(): void
    {
        [$user] = $this->patientUser();
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/appointments')->assertOk()->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_patient_id_query_cannot_change_scope(): void
    {
        [$user, $patient] = $this->patientUser();
        [, $other] = $this->patientUser();
        $staff = $this->staff();
        $own = $this->appointment($patient, $staff, '2026-07-30 09:00:00');
        $this->appointment($other, $staff, '2026-07-31 09:00:00');
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/appointments?patient_id='.$other->id)->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
    }

    /** @return array{User, Patient} */
    private function patientUser(): array
    {
        $user = $this->user();
        $patient = Patient::create(['person_id' => $user->person_id,
            'record_number' => 'VT-'.fake()->unique()->numerify('######'),
            'admission_date' => '2026-01-01', 'administrative_status' => 'ACTIVE']);
        $this->patientRole($user);
        return [$user, $patient];
    }

    private function user(): User
    {
        $person = Person::create(['first_name' => fake()->firstName(), 'first_last_name' => fake()->lastName(),
            'date_of_birth' => '1990-01-01', 'gender' => 'UNSPECIFIED']);
        return User::create(['person_id' => $person->id, 'email' => fake()->unique()->safeEmail(),
            'password' => 'Password#1', 'status' => 'ACTIVE']);
    }

    private function patientRole(User $user): void
    {
        $role = Role::firstOrCreate(['name' => 'PATIENT'], ['active' => true]);
        $user->roles()->attach($role->id, ['active' => true]);
        $user->unsetRelation('roles');
    }

    private function staff(?Specialty $specialty = null): HealthStaff
    {
        $person = Person::create(['first_name' => 'Carlos', 'first_last_name' => 'Ruiz',
            'date_of_birth' => '1980-01-01', 'gender' => 'UNSPECIFIED']);
        return HealthStaff::create(['person_id' => $person->id, 'professional_type' => 'DOCTOR',
            'professional_code' => fake()->unique()->numerify('MED-######'),
            'specialty_id' => $specialty?->id, 'active' => true]);
    }

    private function appointment(Patient $patient, HealthStaff $staff, string $at, string $status = 'SCHEDULED'): Appointment
    {
        return Appointment::create(['patient_id' => $patient->id, 'health_staff_id' => $staff->id,
            'scheduled_at' => $at, 'duration_minutes' => 30, 'reason' => 'Control',
            'status' => $status, 'external_sync' => 'NOT_APPLICABLE']);
    }
}
