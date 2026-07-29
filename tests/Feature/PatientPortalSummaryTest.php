<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Appointment;
use App\Models\HealthStaff;
use App\Models\Measurement;
use App\Models\MeasurementType;
use App\Models\Patient;
use App\Models\Person;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientPortalSummaryTest extends TestCase
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

    public function test_unauthenticated_user_receives_401(): void
    {
        $this->getJson('/api/v1/patient/summary')->assertUnauthorized();
    }

    public function test_authenticated_user_without_patient_role_receives_403(): void
    {
        Sanctum::actingAs($this->createUser());

        $this->getJson('/api/v1/patient/summary')->assertForbidden();
    }

    public function test_patient_role_without_profile_receives_404(): void
    {
        $user = $this->createUser();
        $this->assignPatientRole($user);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/summary')
            ->assertNotFound()
            ->assertExactJson([
                'data' => null,
                'message' => 'No patient profile is associated with this account.',
                'errors' => null,
            ]);
    }

    public function test_summary_returns_scoped_patient_home_data_and_expected_envelope(): void
    {
        [$user, $patient] = $this->createPatientUser([
            'first_name' => 'Ana',
            'middle_name' => 'María',
            'first_last_name' => 'López',
            'second_last_name' => 'Ruiz',
        ]);
        [$otherUser, $otherPatient] = $this->createPatientUser();
        $healthStaff = $this->createHealthStaff();

        $this->createAppointment($patient, $healthStaff, '2026-07-29 09:00:00', 'CANCELLED');
        $next = $this->createAppointment($patient, $healthStaff, '2026-07-29 10:00:00', 'CONFIRMED');
        $this->createAppointment($patient, $healthStaff, '2026-07-29 11:00:00', 'SCHEDULED');
        $this->createAppointment($otherPatient, $healthStaff, '2026-07-29 08:30:00', 'SCHEDULED');
        $deleted = $this->createAppointment($patient, $healthStaff, '2026-07-29 08:15:00', 'SCHEDULED');
        $deleted->delete();

        $type = $this->createMeasurementType();
        $measurementIds = [];
        foreach ([1, 2, 3, 4] as $day) {
            $measurementIds[$day] = $this->createMeasurement(
                $patient,
                $user,
                $type,
                sprintf('2026-07-%02d 08:00:00', $day)
            )->id;
        }
        $this->createMeasurement($otherPatient, $otherUser, $type, '2026-07-05 08:00:00');

        $activeTreatment = $this->createTreatment($patient, $user, 'ACTIVE', '2026-07-10');
        $this->createTreatment($patient, $user, 'FINISHED', '2026-07-20');
        $this->createTreatment($otherPatient, $otherUser, 'ACTIVE', '2026-07-25');

        $this->createAlert($patient, 'NEW', 'CRITICAL');
        $this->createAlert($patient, 'CLASSIFIED', 'HIGH');
        $this->createAlert($patient, 'IN_PROGRESS', 'INFORMATIONAL');
        $this->createAlert($patient, 'CLOSED', 'CRITICAL');
        $this->createAlert($otherPatient, 'NEW', 'CRITICAL');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/patient/summary?patient_id='.$otherPatient->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Patient summary retrieved successfully.')
            ->assertJsonPath('errors', null)
            ->assertJsonPath('data.patient.id', $patient->id)
            ->assertJsonPath('data.patient.record_number', $patient->record_number)
            ->assertJsonPath('data.patient.administrative_status', 'ACTIVE')
            ->assertJsonPath('data.patient.full_name', 'Ana María López Ruiz')
            ->assertJsonPath('data.next_appointment.id', $next->id)
            ->assertJsonPath('data.next_appointment.professional.id', $healthStaff->id)
            ->assertJsonPath('data.next_appointment.professional.full_name', 'Carlos Ruiz')
            ->assertJsonPath('data.latest_measurements.0.id', $measurementIds[4])
            ->assertJsonPath('data.latest_measurements.1.id', $measurementIds[3])
            ->assertJsonPath('data.latest_measurements.2.id', $measurementIds[2])
            ->assertJsonPath('data.latest_measurements.0.measurement_type.id', $type->id)
            ->assertJsonCount(3, 'data.latest_measurements')
            ->assertJsonCount(1, 'data.active_treatments')
            ->assertJsonPath('data.active_treatments.0.id', $activeTreatment->id)
            ->assertJsonPath('data.alerts_summary.open', 3)
            ->assertJsonPath('data.alerts_summary.critical', 1);
    }

    public function test_next_appointment_is_null_when_no_eligible_future_appointment_exists(): void
    {
        [$user, $patient] = $this->createPatientUser();
        $healthStaff = $this->createHealthStaff();
        $this->createAppointment($patient, $healthStaff, '2026-07-28 10:00:00', 'CONFIRMED');
        $this->createAppointment($patient, $healthStaff, '2026-07-30 10:00:00', 'NO_SHOW');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/summary')
            ->assertOk()
            ->assertJsonPath('data.next_appointment', null);
    }

    public function test_summary_returns_empty_collections_and_zero_alert_counts(): void
    {
        [$user] = $this->createPatientUser();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/summary')
            ->assertOk()
            ->assertJsonPath('data.next_appointment', null)
            ->assertJsonCount(0, 'data.latest_measurements')
            ->assertJsonCount(0, 'data.active_treatments')
            ->assertJsonPath('data.alerts_summary.open', 0)
            ->assertJsonPath('data.alerts_summary.critical', 0);
    }

    public function test_optional_appointment_relations_can_be_null(): void
    {
        [$user, $patient] = $this->createPatientUser();
        $healthStaff = $this->createHealthStaff();
        $appointment = $this->createAppointment($patient, $healthStaff, '2026-07-30 10:00:00', 'SCHEDULED');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/summary')
            ->assertOk()
            ->assertJsonPath('data.next_appointment.id', $appointment->id)
            ->assertJsonPath('data.next_appointment.professional.specialty', null);
    }

    /** @param array<string, string> $personOverrides
     *  @return array{User, Patient}
     */
    private function createPatientUser(array $personOverrides = []): array
    {
        $user = $this->createUser($personOverrides);
        $patient = Patient::create([
            'person_id' => $user->person_id,
            'record_number' => 'VT-'.fake()->unique()->numerify('######'),
            'admission_date' => '2026-01-01',
            'administrative_status' => 'ACTIVE',
        ]);
        $this->assignPatientRole($user);

        return [$user, $patient];
    }

    /** @param array<string, string> $overrides */
    private function createUser(array $overrides = []): User
    {
        $person = Person::create(array_merge([
            'first_name' => fake()->firstName(),
            'first_last_name' => fake()->lastName(),
            'date_of_birth' => '1990-01-01',
            'gender' => 'UNSPECIFIED',
        ], $overrides));

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

    private function createHealthStaff(): HealthStaff
    {
        $person = Person::create([
            'first_name' => 'Carlos',
            'first_last_name' => 'Ruiz',
            'date_of_birth' => '1980-01-01',
            'gender' => 'UNSPECIFIED',
        ]);

        return HealthStaff::create([
            'person_id' => $person->id,
            'professional_type' => 'DOCTOR',
            'professional_code' => fake()->unique()->numerify('MED-######'),
            'specialty_id' => null,
            'active' => true,
        ]);
    }

    private function createAppointment(Patient $patient, HealthStaff $staff, string $scheduledAt, string $status): Appointment
    {
        return Appointment::create([
            'patient_id' => $patient->id,
            'health_staff_id' => $staff->id,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 30,
            'reason' => 'Control',
            'status' => $status,
            'external_sync' => 'NOT_APPLICABLE',
        ]);
    }

    private function createMeasurementType(): MeasurementType
    {
        return MeasurementType::create([
            'name' => 'Weight',
            'base_unit' => 'kg',
            'decimals' => 2,
            'active' => true,
        ]);
    }

    private function createMeasurement(Patient $patient, User $author, MeasurementType $type, string $measuredAt): Measurement
    {
        return Measurement::create([
            'patient_id' => $patient->id,
            'measurement_type_id' => $type->id,
            'value' => 70.5,
            'unit' => 'kg',
            'measured_at' => $measuredAt,
            'origin' => 'PATIENT',
            'author_user_id' => $author->id,
            'observation' => null,
        ]);
    }

    private function createTreatment(Patient $patient, User $prescriber, string $status, string $startDate): Treatment
    {
        return Treatment::create([
            'patient_id' => $patient->id,
            'diagnosis_id' => null,
            'indications' => 'Follow prescribed plan.',
            'start_date' => $startDate,
            'end_date' => null,
            'status' => $status,
            'prescribed_by' => $prescriber->id,
        ]);
    }

    private function createAlert(Patient $patient, string $status, string $severity): Alert
    {
        return Alert::create([
            'patient_id' => $patient->id,
            'measurement_id' => null,
            'type' => 'FOLLOW_UP',
            'severity' => $severity,
            'status' => $status,
            'description' => 'Follow-up signal.',
            'generated_at' => '2026-07-29 07:00:00',
            'closed_at' => $status === 'CLOSED' ? '2026-07-29 07:30:00' : null,
        ]);
    }
}
