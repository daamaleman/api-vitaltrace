<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Appointment;
use App\Models\ClinicalEvolution;
use App\Models\Diagnosis;
use App\Models\HealthStaff;
use App\Models\Measurement;
use App\Models\MeasurementType;
use App\Models\Patient;
use App\Models\Person;
use App\Models\ProfessionalAssignment;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class NursePortalTest extends TestCase
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
        Carbon::setTestNow('2026-09-02 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_authentication_and_role_are_required(): void
    {
        $this->getJson('/api/v1/nurse/patients')->assertUnauthorized();

        $patientUser = $this->userWithRole('PATIENT');
        Sanctum::actingAs($patientUser);
        $this->getJson('/api/v1/nurse/patients')->assertForbidden();

        $relativeUser = $this->userWithRole('RELATIVE');
        Sanctum::actingAs($relativeUser);
        $this->getJson('/api/v1/nurse/patients')->assertForbidden();
    }

    public function test_only_current_assignments_appear_and_multiple_patients_are_supported(): void
    {
        [$nurse, $staff] = $this->nurse();
        $activeOne = $this->patient('Ana');
        $activeTwo = $this->patient('Luis');
        $future = $this->patient('Future');
        $expired = $this->patient('Expired');
        $finished = $this->patient('Finished');
        $suspended = $this->patient('Suspended');
        $unassigned = $this->patient('Other');

        $this->assign($staff, $activeOne);
        $this->assign($staff, $activeTwo, 'ACTIVE', '2026-08-01', '2026-09-02');
        $this->assign($staff, $future, 'ACTIVE', '2026-09-03');
        $this->assign($staff, $expired, 'ACTIVE', '2026-08-01', '2026-09-01');
        $this->assign($staff, $finished, 'FINISHED');
        $this->assign($staff, $suspended, 'SUSPENDED');
        Sanctum::actingAs($nurse);

        $response = $this->getJson('/api/v1/nurse/patients')->assertOk()->assertJsonCount(2, 'data');
        $ids = collect($response->json('data'))->pluck('patient_id');
        $this->assertTrue($ids->contains($activeOne->id));
        $this->assertTrue($ids->contains($activeTwo->id));
        $this->assertFalse($ids->contains($unassigned->id));
    }


    public function test_each_patient_receives_its_own_latest_measurement(): void
    {
        [$nurse, $staff] = $this->nurse();
        $patientA = $this->patient('Measurement A');
        $patientB = $this->patient('Measurement B');
        $this->assign($staff, $patientA);
        $this->assign($staff, $patientB);
        $type = MeasurementType::create(['name' => 'Blood pressure', 'base_unit' => 'mmHg', 'decimals' => 0, 'active' => true]);

        Measurement::create(['patient_id' => $patientA->id, 'measurement_type_id' => $type->id, 'value' => 100, 'unit' => 'mmHg', 'measured_at' => '2026-09-02 08:00:00', 'origin' => 'PATIENT', 'author_user_id' => $nurse->id]);
        Measurement::create(['patient_id' => $patientA->id, 'measurement_type_id' => $type->id, 'value' => 120, 'unit' => 'mmHg', 'measured_at' => '2026-09-02 09:00:00', 'origin' => 'PATIENT', 'author_user_id' => $nurse->id]);
        Measurement::create(['patient_id' => $patientB->id, 'measurement_type_id' => $type->id, 'value' => 80, 'unit' => 'mmHg', 'measured_at' => '2026-09-02 08:00:00', 'origin' => 'PATIENT', 'author_user_id' => $nurse->id]);
        Measurement::create(['patient_id' => $patientB->id, 'measurement_type_id' => $type->id, 'value' => 95, 'unit' => 'mmHg', 'measured_at' => '2026-09-02 10:00:00', 'origin' => 'PATIENT', 'author_user_id' => $nurse->id]);

        Sanctum::actingAs($nurse);
        $items = collect($this->getJson('/api/v1/nurse/patients')->assertOk()->json('data'))->keyBy('patient_id');

        $this->assertSame('120.000', $items[$patientA->id]['last_measurement']['value']);
        $this->assertSame('95.000', $items[$patientB->id]['last_measurement']['value']);
    }
    /** @dataProvider invalidAssignmentProvider */
    public function test_invalid_assignment_cannot_access_patient(string $status, string $start, ?string $end): void
    {
        [$nurse, $staff] = $this->nurse();
        $patient = $this->patient('Denied');
        $this->assign($staff, $patient, $status, $start, $end);
        Sanctum::actingAs($nurse);

        $this->getJson("/api/v1/nurse/patients/{$patient->id}/summary")->assertForbidden();
    }

    public static function invalidAssignmentProvider(): array
    {
        return [
            'future' => ['ACTIVE', '2026-09-03', null],
            'expired' => ['ACTIVE', '2026-08-01', '2026-09-01'],
            'finished' => ['FINISHED', '2026-08-01', null],
            'suspended' => ['SUSPENDED', '2026-08-01', null],
        ];
    }

    public function test_inactive_or_non_nurse_health_staff_is_forbidden(): void
    {
        [$inactiveUser, $inactiveStaff] = $this->nurse(false);
        $patient = $this->patient('Inactive');
        $this->assign($inactiveStaff, $patient);
        Sanctum::actingAs($inactiveUser);
        $this->getJson('/api/v1/nurse/patients')->assertForbidden();

        $user = $this->userWithRole('NURSE');
        $doctorStaff = $this->staff($user, 'DOCTOR');
        $this->assign($doctorStaff, $patient);
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/nurse/patients')->assertForbidden();
    }

    public function test_dashboard_profile_summary_and_search_are_scoped(): void
    {
        [$nurse, $staff] = $this->nurse();
        $patient = $this->patient('Carlos', 'VT-100');
        $other = $this->patient('Other', 'VT-200');
        $this->assign($staff, $patient);
        $this->appointment($patient, $staff);
        $this->alert($patient, 'NEW', 'CRITICAL');
        $this->alert($other, 'NEW', 'CRITICAL');
        Sanctum::actingAs($nurse);

        $this->getJson('/api/v1/nurse/summary')->assertOk()
            ->assertJsonPath('data.assigned_patients_count', 1)
            ->assertJsonPath('data.alerts.total_pending', 1)
            ->assertJsonPath('data.alerts.critical', 1)
            ->assertJsonPath('data.appointments.upcoming_count', 1);
        $this->getJson('/api/v1/nurse/patients?search=VT-100')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/nurse/patients?search=Other')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/nurse/patients/{$patient->id}/profile")->assertOk()->assertJsonMissingPath('data.administrative_notes');
        $this->getJson("/api/v1/nurse/patients/{$patient->id}/summary")->assertOk()->assertJsonPath('data.patient.patient_id', $patient->id);
        $this->getJson("/api/v1/nurse/patients/{$other->id}/profile")->assertForbidden();
    }

    public function test_appointments_are_scoped_to_nurse_and_assigned_patient(): void
    {
        [$nurse, $staff] = $this->nurse();
        $patient = $this->patient('Assigned');
        $other = $this->patient('Other');
        $this->assign($staff, $patient);
        $own = $this->appointment($patient, $staff);
        $otherAppointment = $this->appointment($other, $staff);
        Sanctum::actingAs($nurse);

        $this->getJson('/api/v1/nurse/appointments')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->getJson("/api/v1/nurse/patients/{$patient->id}/appointments")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/nurse/appointments/{$otherAppointment->id}")->assertForbidden();
    }


    public function test_nurse_appointment_contract_and_next_appointment_are_scoped(): void
    {
        [$nurse, $staff] = $this->nurse();
        $patient = $this->patient('Appointment Patient');
        $withoutAppointment = $this->patient('No Appointment');
        $this->assign($staff, $patient);
        $this->assign($staff, $withoutAppointment);
        $appointment = $this->appointment($patient, $staff);
        Sanctum::actingAs($nurse);

        $this->getJson('/api/v1/nurse/appointments')->assertOk()
            ->assertJsonPath('data.0.id', $appointment->id)
            ->assertJsonPath('data.0.patient_id', $patient->id)
            ->assertJsonPath('data.0.scheduled_at', '2026-09-04 08:00:00')
            ->assertJsonPath('data.0.duration_minutes', 30)
            ->assertJsonPath('data.0.reason', 'Control')
            ->assertJsonPath('data.0.status', 'SCHEDULED')
            ->assertJsonPath('data.0.professional.professional_type', 'NURSE');
        $this->getJson("/api/v1/nurse/patients/{$patient->id}/appointments")->assertOk()->assertJsonPath('data.0.id', $appointment->id);
        $this->getJson("/api/v1/nurse/appointments/{$appointment->id}")->assertOk()
            ->assertJsonPath('data.id', $appointment->id)
            ->assertJsonPath('data.patient_id', $patient->id)
            ->assertJsonPath('data.professional.professional_type', 'NURSE');
        $this->getJson('/api/v1/nurse/summary')->assertOk()
            ->assertJsonPath('data.appointments.upcoming_count', 1)
            ->assertJsonPath('data.appointments.next.0.id', $appointment->id);

        $patients = collect($this->getJson('/api/v1/nurse/patients')->assertOk()->json('data'))->keyBy('patient_id');
        $this->assertSame($appointment->id, $patients[$patient->id]['next_appointment']['id']);
        $this->assertNull($patients[$withoutAppointment->id]['next_appointment']);
    }
    public function test_measurement_creation_derives_identity_and_rejects_tampering(): void
    {
        [$nurse, $staff] = $this->nurse();
        $patient = $this->patient('Assigned');
        $other = $this->patient('Other');
        $this->assign($staff, $patient);
        $type = MeasurementType::create(['name' => 'Glucose', 'base_unit' => 'mg/dL', 'decimals' => 0, 'active' => true]);
        Sanctum::actingAs($nurse);

        $this->postJson("/api/v1/nurse/patients/{$patient->id}/measurements", [
            'measurement_type_id' => $type->id,
            'value' => 100,
            'unit' => 'mg/dL',
            'measured_at' => now()->toDateTimeString(),
            'observation' => 'Control',
            'patient_id' => $other->id,
            'origin' => 'PATIENT',
            'author_user_id' => 999,
        ])->assertCreated()->assertJsonPath('data.patient_id', $patient->id)->assertJsonPath('data.origin', 'NURSE');

        $measurement = Measurement::query()->sole();
        $this->assertSame($patient->id, $measurement->patient_id);
        $this->assertSame($nurse->id, $measurement->author_user_id);
        $this->assertSame('NURSE', $measurement->origin);
        $this->getJson("/api/v1/nurse/patients/{$patient->id}/measurements")->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/nurse/patients/{$other->id}/measurements", [])->assertForbidden();
    }

    public function test_measurement_validation_and_active_catalog(): void
    {
        [$nurse, $staff] = $this->nurse();
        $patient = $this->patient('Assigned');
        $this->assign($staff, $patient);
        $active = MeasurementType::create(['name' => 'Oxygen', 'base_unit' => '%', 'decimals' => 0, 'active' => true]);
        MeasurementType::create(['name' => 'Inactive', 'base_unit' => 'x', 'decimals' => 0, 'active' => false]);
        Sanctum::actingAs($nurse);

        $this->getJson('/api/v1/nurse/measurement-types')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $active->id);
        $this->postJson("/api/v1/nurse/patients/{$patient->id}/measurements", [
            'measurement_type_id' => 99999, 'value' => 1, 'unit' => '%', 'measured_at' => now()->toDateTimeString(),
        ])->assertUnprocessable();
        $this->postJson("/api/v1/nurse/patients/{$patient->id}/measurements", [
            'measurement_type_id' => $active->id, 'value' => 1, 'unit' => 'wrong', 'measured_at' => now()->toDateTimeString(),
        ])->assertUnprocessable();
    }

    public function test_clinical_projections_are_read_only_for_nurse(): void
    {
        [$nurse, $staff] = $this->nurse();
        $patient = $this->patient('Assigned');
        $this->assign($staff, $patient);
        $diagnosis = Diagnosis::create(['patient_id' => $patient->id, 'description' => 'Test', 'diagnosis_date' => today(), 'status' => 'ACTIVE', 'registered_by' => $nurse->id]);
        Treatment::create(['patient_id' => $patient->id, 'indications' => 'Plan', 'start_date' => today(), 'status' => 'ACTIVE', 'prescribed_by' => $nurse->id]);
        ClinicalEvolution::create(['patient_id' => $patient->id, 'registered_by' => $nurse->id, 'clinical_summary' => 'Stable', 'status' => 'STABLE', 'recorded_at' => now()]);
        Sanctum::actingAs($nurse);

        $this->getJson("/api/v1/nurse/patients/{$patient->id}/diagnoses")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/nurse/patients/{$patient->id}/treatments")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/nurse/patients/{$patient->id}/clinical-history")->assertOk()->assertJsonCount(1, 'data.evolutions');
        $this->postJson('/api/v1/diagnoses', [])->assertForbidden();
        $this->patchJson("/api/v1/diagnoses/{$diagnosis->id}", [])->assertForbidden();
        $this->deleteJson("/api/v1/diagnoses/{$diagnosis->id}")->assertForbidden();
        $this->postJson("/api/v1/clinical/patients/{$patient->id}/treatments", [])->assertForbidden();
        $this->postJson("/api/v1/clinical/patients/{$patient->id}/ranges", [])->assertForbidden();
        $this->postJson("/api/v1/clinical/patients/{$patient->id}/evolutions", [])->assertForbidden();
    }

    public function test_alerts_are_scoped_and_actions_write_server_managed_history(): void
    {
        [$nurse, $staff] = $this->nurse();
        $patient = $this->patient('Assigned');
        $other = $this->patient('Other');
        $this->assign($staff, $patient);
        $own = $this->alert($patient);
        $foreign = $this->alert($other);
        Sanctum::actingAs($nurse);

        $this->getJson('/api/v1/nurse/alerts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->getJson("/api/v1/nurse/patients/{$patient->id}/alerts")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/nurse/alerts/{$foreign->id}")->assertForbidden();
        $this->postJson("/api/v1/nurse/alerts/{$own->id}/classify", ['comment' => 'Reviewed'])->assertOk()->assertJsonPath('data.status', 'CLASSIFIED');
        $this->postJson("/api/v1/nurse/alerts/{$own->id}/escalate", ['comment' => 'Escalated'])->assertOk()->assertJsonPath('data.status', 'ESCALATED');
        $this->assertDatabaseCount('alert_history', 2);
        $this->postJson("/api/v1/nurse/alerts/{$foreign->id}/classify", [])->assertForbidden();
    }

    public function test_nurse_cannot_close_alert_or_use_legacy_alert_mutations(): void
    {
        [$nurse, $staff] = $this->nurse();
        $patient = $this->patient('Assigned');
        $this->assign($staff, $patient);
        $alert = $this->alert($patient);
        Sanctum::actingAs($nurse);

        $this->postJson("/api/v1/alerts/{$alert->id}/close", [])->assertForbidden();
        $this->postJson('/api/v1/alert-history', [])->assertForbidden();
        $this->patchJson("/api/v1/alerts/{$alert->id}", [])->assertForbidden();
        $this->deleteJson("/api/v1/alerts/{$alert->id}")->assertForbidden();
    }

    public function test_doctor_keeps_legacy_clinical_access(): void
    {
        $doctor = $this->userWithRole('DOCTOR');
        Sanctum::actingAs($doctor);

        $this->getJson('/api/v1/diagnoses')->assertOk();
        $this->getJson('/api/v1/alerts')->assertOk();
    }

    /** @return array{User, HealthStaff} */
    private function nurse(bool $active = true): array
    {
        $user = $this->userWithRole('NURSE');

        return [$user, $this->staff($user, 'NURSE', $active)];
    }

    private function staff(User $user, string $type, bool $active = true): HealthStaff
    {
        return HealthStaff::create(['person_id' => $user->person_id, 'professional_type' => $type, 'professional_code' => fake()->unique()->bothify('PRO-####'), 'active' => $active]);
    }

    private function userWithRole(string $roleName): User
    {
        $person = Person::create(['first_name' => fake()->firstName(), 'first_last_name' => fake()->lastName(), 'date_of_birth' => '1990-01-01', 'gender' => 'UNSPECIFIED']);
        $user = User::create(['person_id' => $person->id, 'email' => fake()->unique()->safeEmail(), 'password' => 'Password#1', 'status' => 'ACTIVE']);
        $role = Role::firstOrCreate(['name' => $roleName], ['active' => true]);
        $user->roles()->attach($role->id, ['active' => true]);
        $user->unsetRelation('roles');

        return $user;
    }

    private function patient(string $name, ?string $record = null): Patient
    {
        $person = Person::create(['first_name' => $name, 'first_last_name' => 'Patient', 'date_of_birth' => '1990-01-01', 'gender' => 'UNSPECIFIED']);

        return Patient::create(['person_id' => $person->id, 'record_number' => $record ?? fake()->unique()->numerify('VT-######'), 'admission_date' => '2026-01-01', 'administrative_status' => 'ACTIVE']);
    }

    private function assign(HealthStaff $staff, Patient $patient, string $status = 'ACTIVE', string $start = '2026-08-01', ?string $end = null): ProfessionalAssignment
    {
        $assignerId = User::query()->where('person_id', $staff->person_id)->value('id');

        return ProfessionalAssignment::create(['patient_id' => $patient->id, 'health_staff_id' => $staff->id, 'assignment_type' => 'NURSE', 'start_date' => $start, 'end_date' => $end, 'status' => $status, 'assigned_by' => $assignerId]);
    }

    private function appointment(Patient $patient, HealthStaff $staff): Appointment
    {
        return Appointment::create(['patient_id' => $patient->id, 'health_staff_id' => $staff->id, 'scheduled_at' => now()->addDays(2), 'duration_minutes' => 30, 'reason' => 'Control', 'status' => 'SCHEDULED', 'external_sync' => 'NOT_APPLICABLE']);
    }

    private function alert(Patient $patient, string $status = 'NEW', string $severity = 'HIGH'): Alert
    {
        return Alert::create(['patient_id' => $patient->id, 'type' => 'MEASUREMENT', 'severity' => $severity, 'status' => $status, 'description' => 'Follow-up', 'generated_at' => now()]);
    }
}
