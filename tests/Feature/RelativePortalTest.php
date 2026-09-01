<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Measurement;
use App\Models\MeasurementType;
use App\Models\Patient;
use App\Models\PatientRelative;
use App\Models\Person;
use App\Models\Relative;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class RelativePortalTest extends TestCase
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
        Carbon::setTestNow('2026-09-01 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_unauthenticated_user_receives_401(): void
    {
        $this->getJson('/api/v1/relative/patients')->assertUnauthorized();
    }

    public function test_patient_role_receives_403_on_relative_routes(): void
    {
        [$user] = $this->patientUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/relative/patients')->assertForbidden();
    }

    public function test_linked_patients_returns_only_current_relatives_valid_active_relations(): void
    {
        [$relativeUser, $relative] = $this->relativeUser();
        $activeOne = $this->patient('Ana');
        $activeTwo = $this->patient('Luis');
        $pending = $this->patient('Marta');
        [, $otherRelative] = $this->relativeUser();
        $otherPatient = $this->patient('Otro');

        $this->link($relative, $activeOne, 'ACTIVE');
        $this->link($relative, $activeTwo, 'ACTIVE');
        $this->link($relative, $pending, 'PENDING');
        $this->link($otherRelative, $otherPatient, 'ACTIVE');
        Sanctum::actingAs($relativeUser);

        $this->getJson('/api/v1/relative/patients')
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $activeOne->id)
            ->assertJsonPath('data.0.full_name', 'Ana Patient')
            ->assertJsonPath('data.0.status', 'ACTIVE')
            ->assertJsonPath('data.1.id', $activeTwo->id);
    }

    public function test_active_relative_can_read_all_supported_patient_projections(): void
    {
        [$relativeUser, $relative] = $this->relativeUser();
        $patient = $this->patient('Ana');
        $this->link($relative, $patient, 'ACTIVE');
        Sanctum::actingAs($relativeUser);

        foreach (['summary', 'profile', 'appointments', 'measurements', 'treatments', 'clinical-history'] as $endpoint) {
            $this->getJson("/api/v1/relative/patients/{$patient->id}/{$endpoint}")
                ->assertOk();
        }
    }

    public function test_measurements_and_treatments_are_scoped_to_authorized_patient(): void
    {
        [$relativeUser, $relative] = $this->relativeUser();
        $patient = $this->patient('Ana');
        $otherPatient = $this->patient('Other');
        $this->link($relative, $patient, 'ACTIVE');
        $type = MeasurementType::create(['name' => 'Weight', 'base_unit' => 'kg', 'decimals' => 1, 'active' => true]);
        $own = Measurement::create(['patient_id' => $patient->id, 'measurement_type_id' => $type->id, 'value' => 70, 'unit' => 'kg', 'measured_at' => now(), 'origin' => 'PATIENT', 'author_user_id' => $relativeUser->id]);
        Measurement::create(['patient_id' => $otherPatient->id, 'measurement_type_id' => $type->id, 'value' => 80, 'unit' => 'kg', 'measured_at' => now(), 'origin' => 'PATIENT', 'author_user_id' => $relativeUser->id]);
        $ownTreatment = Treatment::create(['patient_id' => $patient->id, 'indications' => 'Plan A', 'start_date' => today(), 'status' => 'ACTIVE', 'prescribed_by' => $relativeUser->id]);
        Treatment::create(['patient_id' => $otherPatient->id, 'indications' => 'Plan B', 'start_date' => today(), 'status' => 'ACTIVE', 'prescribed_by' => $relativeUser->id]);
        Sanctum::actingAs($relativeUser);

        $this->getJson("/api/v1/relative/patients/{$patient->id}/measurements")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->getJson("/api/v1/relative/patients/{$patient->id}/treatments")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownTreatment->id);
        $this->getJson("/api/v1/relative/patients/{$otherPatient->id}/measurements")->assertForbidden();
        $this->getJson('/api/v1/relative/patients/999/measurements')->assertForbidden();
    }

    /** @dataProvider deniedStatusProvider */
    public function test_non_active_or_out_of_date_relations_receive_403(string $status, string $startDate, ?string $endDate): void
    {
        [$relativeUser, $relative] = $this->relativeUser();
        $patient = $this->patient('Denied');
        $this->link($relative, $patient, $status, $startDate, $endDate);
        Sanctum::actingAs($relativeUser);

        $this->getJson("/api/v1/relative/patients/{$patient->id}/summary")->assertForbidden();
    }

    public static function deniedStatusProvider(): array
    {
        return [
            'pending' => ['PENDING', '2026-08-01', null],
            'revoked' => ['REVOKED', '2026-08-01', null],
            'expired status' => ['EXPIRED', '2026-08-01', null],
            'expired date' => ['ACTIVE', '2026-08-01', '2026-08-31'],
            'not started' => ['ACTIVE', '2026-09-02', null],
        ];
    }

    /** @return array{User, Relative} */
    private function relativeUser(): array
    {
        $user = $this->user();
        $relative = Relative::create(['person_id' => $user->person_id]);
        $this->assignRole($user, 'RELATIVE');
        return [$user, $relative];
    }

    /** @return array{User, Patient} */
    private function patientUser(): array
    {
        $user = $this->user();
        $patient = Patient::create(['person_id' => $user->person_id, 'record_number' => fake()->unique()->numerify('VT-######'), 'admission_date' => '2026-01-01', 'administrative_status' => 'ACTIVE']);
        $this->assignRole($user, 'PATIENT');
        return [$user, $patient];
    }

    private function patient(string $firstName): Patient
    {
        $person = Person::create(['first_name' => $firstName, 'first_last_name' => 'Patient', 'date_of_birth' => '1990-01-01', 'gender' => 'UNSPECIFIED']);
        return Patient::create(['person_id' => $person->id, 'record_number' => fake()->unique()->numerify('VT-######'), 'admission_date' => '2026-01-01', 'administrative_status' => 'ACTIVE']);
    }

    private function user(): User
    {
        $person = Person::create(['first_name' => fake()->firstName(), 'first_last_name' => fake()->lastName(), 'date_of_birth' => '1990-01-01', 'gender' => 'UNSPECIFIED']);
        return User::create(['person_id' => $person->id, 'email' => fake()->unique()->safeEmail(), 'password' => 'Password#1', 'status' => 'ACTIVE']);
    }

    private function assignRole(User $user, string $name): void
    {
        $role = Role::firstOrCreate(['name' => $name], ['active' => true]);
        $user->roles()->attach($role->id, ['active' => true]);
        $user->unsetRelation('roles');
    }

    private function link(Relative $relative, Patient $patient, string $status, string $startDate = '2026-08-01', ?string $endDate = null): PatientRelative
    {
        return PatientRelative::create(['patient_id' => $patient->id, 'relative_id' => $relative->id, 'relationship' => 'FAMILY', 'scope' => null, 'status' => $status, 'start_date' => $startDate, 'end_date' => $endDate]);
    }
}