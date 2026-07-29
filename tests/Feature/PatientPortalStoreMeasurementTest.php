<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MeasurementType;
use App\Models\Patient;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientPortalStoreMeasurementTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_unauthenticated_user_receives_401(): void
    {
        $this->postJson('/api/v1/patient/measurements', $this->validPayload())
            ->assertUnauthorized();
    }

    public function test_authenticated_user_without_patient_role_receives_403(): void
    {
        $user = $this->createUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/patient/measurements', $this->validPayload())
            ->assertForbidden()
            ->assertExactJson([
                'data' => null,
                'message' => 'You do not have permission to perform this action.',
                'errors' => null,
            ]);
    }

    public function test_authenticated_patient_can_register_a_valid_measurement(): void
    {
        [$user, $patient] = $this->createPatientUser();
        $measurementType = $this->createMeasurementType();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/patient/measurements', $this->validPayload([
            'measurement_type_id' => $measurementType->id,
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Measurement registered successfully.')
            ->assertJsonPath('errors', null)
            ->assertJsonPath('data.patient_id', $patient->id)
            ->assertJsonPath('data.measurement_type_id', $measurementType->id)
            ->assertJsonPath('data.origin', 'PATIENT')
            ->assertJsonPath('data.author_user_id', $user->id)
            ->assertJsonPath('data.measurement_type.id', $measurementType->id)
            ->assertJsonPath('data.measurement_type.name', 'Blood pressure');

        $this->assertDatabaseHas('measurements', [
            'patient_id' => $patient->id,
            'measurement_type_id' => $measurementType->id,
            'unit' => 'mmHg',
            'origin' => 'PATIENT',
            'author_user_id' => $user->id,
            'observation' => 'At rest',
        ]);
    }

    public function test_server_ignores_patient_author_and_origin_from_body(): void
    {
        [$user, $patient] = $this->createPatientUser();
        [$otherUser, $otherPatient] = $this->createPatientUser();
        $measurementType = $this->createMeasurementType();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/patient/measurements', $this->validPayload([
            'measurement_type_id' => $measurementType->id,
            'patient_id' => $otherPatient->id,
            'origin' => 'DOCTOR',
            'author_user_id' => $otherUser->id,
        ]))->assertCreated();

        $this->assertDatabaseHas('measurements', [
            'patient_id' => $patient->id,
            'origin' => 'PATIENT',
            'author_user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('measurements', [
            'patient_id' => $otherPatient->id,
            'author_user_id' => $otherUser->id,
        ]);
    }

    public function test_missing_measurement_type_id_receives_422(): void
    {
        [$user] = $this->createPatientUser();
        Sanctum::actingAs($user);
        $payload = $this->validPayload();
        unset($payload['measurement_type_id']);

        $this->postJson('/api/v1/patient/measurements', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('measurement_type_id');
    }

    public function test_missing_value_receives_422(): void
    {
        [$user] = $this->createPatientUser();
        Sanctum::actingAs($user);
        $payload = $this->validPayload();
        unset($payload['value']);

        $this->postJson('/api/v1/patient/measurements', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');
    }

    public function test_nonexistent_measurement_type_receives_422(): void
    {
        [$user] = $this->createPatientUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/patient/measurements', $this->validPayload([
            'measurement_type_id' => 999999,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('measurement_type_id');
    }

    public function test_patient_role_without_associated_profile_receives_404(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'PATIENT');
        $measurementType = $this->createMeasurementType();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/patient/measurements', $this->validPayload([
            'measurement_type_id' => $measurementType->id,
        ]))
            ->assertNotFound()
            ->assertExactJson([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ]);
    }

    /** @return array{User, Patient} */
    private function createPatientUser(): array
    {
        $user = $this->createUser();
        $patient = Patient::create([
            'person_id' => $user->person_id,
            'record_number' => 'VT-'.fake()->unique()->numerify('######'),
            'admission_date' => '2026-07-29',
            'administrative_status' => 'ACTIVE',
        ]);
        $this->assignRole($user, 'PATIENT');

        return [$user, $patient];
    }

    private function createUser(): User
    {
        $person = Person::create([
            'first_name' => fake()->firstName(),
            'first_last_name' => fake()->lastName(),
            'date_of_birth' => '1990-01-01',
            'gender' => 'UNSPECIFIED',
        ]);

        return User::create([
            'person_id' => $person->id,
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password#1',
            'status' => 'ACTIVE',
        ]);
    }

    private function assignRole(User $user, string $name): void
    {
        $role = Role::firstOrCreate(['name' => $name], ['active' => true]);
        $user->roles()->attach($role->id, ['active' => true]);
        $user->unsetRelation('roles');
    }

    private function createMeasurementType(): MeasurementType
    {
        return MeasurementType::create([
            'name' => 'Blood pressure',
            'base_unit' => 'mmHg',
            'decimals' => 2,
            'active' => true,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'measurement_type_id' => 1,
            'value' => 120.5,
            'unit' => 'mmHg',
            'measured_at' => '2026-07-29 09:30:00',
            'observation' => 'At rest',
        ], $overrides);
    }
}
