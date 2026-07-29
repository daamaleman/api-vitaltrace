<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Measurement;
use App\Models\MeasurementType;
use App\Models\Patient;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientPortalMeasurementsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_unauthenticated_user_receives_401(): void
    {
        $this->getJson('/api/v1/patient/measurements')->assertUnauthorized();
    }

    public function test_authenticated_user_without_patient_role_receives_403(): void
    {
        Sanctum::actingAs($this->createUser());

        $this->getJson('/api/v1/patient/measurements')->assertForbidden();
    }

    public function test_patient_role_without_profile_receives_404(): void
    {
        $user = $this->createUser();
        $this->assignPatientRole($user);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements')
            ->assertNotFound()
            ->assertExactJson([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ]);
    }

    public function test_patient_receives_only_own_measurements_newest_first_with_type(): void
    {
        [$user, $patient] = $this->createPatientUser();
        [$otherUser, $otherPatient] = $this->createPatientUser();
        $type = $this->createMeasurementType('Blood pressure');
        $oldest = $this->createMeasurement($patient, $user, $type, '2026-07-01 08:00:00');
        $newest = $this->createMeasurement($patient, $user, $type, '2026-07-03 08:00:00');
        $middle = $this->createMeasurement($patient, $user, $type, '2026-07-02 08:00:00');
        $other = $this->createMeasurement($otherPatient, $otherUser, $type, '2026-07-04 08:00:00');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/patient/measurements')->assertOk();

        $response
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $newest->id)
            ->assertJsonPath('data.1.id', $middle->id)
            ->assertJsonPath('data.2.id', $oldest->id)
            ->assertJsonPath('data.0.measurement_type.id', $type->id)
            ->assertJsonMissing(['id' => $other->id]);
    }

    public function test_listing_is_paginated_with_fifteen_items_per_page(): void
    {
        [$user, $patient] = $this->createPatientUser();
        $type = $this->createMeasurementType('Weight');
        foreach (range(1, 16) as $day) {
            $this->createMeasurement($patient, $user, $type, sprintf('2026-07-%02d 08:00:00', $day));
        }
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_measurement_type_filter_works(): void
    {
        [$user, $patient] = $this->createPatientUser();
        $pressure = $this->createMeasurementType('Blood pressure');
        $weight = $this->createMeasurementType('Weight');
        $included = $this->createMeasurement($patient, $user, $pressure, '2026-07-01 08:00:00');
        $excluded = $this->createMeasurement($patient, $user, $weight, '2026-07-02 08:00:00');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements?measurement_type_id='.$pressure->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $included->id)
            ->assertJsonMissing(['id' => $excluded->id]);
    }

    public function test_date_from_filter_works(): void
    {
        [$user, $patient] = $this->createPatientUser();
        $type = $this->createMeasurementType('Temperature');
        $this->createMeasurement($patient, $user, $type, '2026-07-09 23:59:59');
        $included = $this->createMeasurement($patient, $user, $type, '2026-07-10 00:00:00');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements?date_from=2026-07-10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $included->id);
    }

    public function test_date_to_filter_works(): void
    {
        [$user, $patient] = $this->createPatientUser();
        $type = $this->createMeasurementType('Temperature');
        $included = $this->createMeasurement($patient, $user, $type, '2026-07-10 23:59:59');
        $this->createMeasurement($patient, $user, $type, '2026-07-11 00:00:00');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements?date_to=2026-07-10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $included->id);
    }

    public function test_date_range_filter_is_inclusive(): void
    {
        [$user, $patient] = $this->createPatientUser();
        $type = $this->createMeasurementType('Glucose');
        $this->createMeasurement($patient, $user, $type, '2026-07-09 12:00:00');
        $from = $this->createMeasurement($patient, $user, $type, '2026-07-10 00:00:00');
        $to = $this->createMeasurement($patient, $user, $type, '2026-07-12 23:59:59');
        $this->createMeasurement($patient, $user, $type, '2026-07-13 00:00:00');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements?date_from=2026-07-10&date_to=2026-07-12')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $to->id)
            ->assertJsonPath('data.1.id', $from->id);
    }

    public function test_nonexistent_measurement_type_filter_receives_422(): void
    {
        [$user] = $this->createPatientUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements?measurement_type_id=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('measurement_type_id');
    }

    public function test_date_to_before_date_from_receives_422(): void
    {
        [$user] = $this->createPatientUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements?date_from=2026-07-12&date_to=2026-07-11')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');
    }

    public function test_patient_without_measurements_receives_empty_paginated_collection(): void
    {
        [$user] = $this->createPatientUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_patient_id_query_parameter_cannot_change_scope(): void
    {
        [$user, $patient] = $this->createPatientUser();
        [$otherUser, $otherPatient] = $this->createPatientUser();
        $type = $this->createMeasurementType('Oxygen saturation');
        $own = $this->createMeasurement($patient, $user, $type, '2026-07-01 08:00:00');
        $other = $this->createMeasurement($otherPatient, $otherUser, $type, '2026-07-02 08:00:00');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/measurements?patient_id='.$otherPatient->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id)
            ->assertJsonMissing(['id' => $other->id]);
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
        $this->assignPatientRole($user);

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

    private function assignPatientRole(User $user): void
    {
        $role = Role::firstOrCreate(['name' => 'PATIENT'], ['active' => true]);
        $user->roles()->attach($role->id, ['active' => true]);
        $user->unsetRelation('roles');
    }

    private function createMeasurementType(string $name): MeasurementType
    {
        return MeasurementType::create([
            'name' => $name,
            'base_unit' => 'unit',
            'decimals' => 2,
            'active' => true,
        ]);
    }

    private function createMeasurement(
        Patient $patient,
        User $author,
        MeasurementType $type,
        string $measuredAt
    ): Measurement {
        return Measurement::create([
            'patient_id' => $patient->id,
            'measurement_type_id' => $type->id,
            'value' => 120.5,
            'unit' => $type->base_unit,
            'measured_at' => $measuredAt,
            'origin' => 'PATIENT',
            'author_user_id' => $author->id,
            'observation' => null,
        ]);
    }
}
