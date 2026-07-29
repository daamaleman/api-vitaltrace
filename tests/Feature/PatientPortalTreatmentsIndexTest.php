<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Diagnosis, HealthStaff, Patient, Person, Role, Specialty, Treatment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientPortalTreatmentsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    public function test_access_controls_and_missing_profile(): void
    {
        $this->getJson('/api/v1/patient/treatments')->assertUnauthorized();
        Sanctum::actingAs($this->user());
        $this->getJson('/api/v1/patient/treatments')->assertForbidden();

        $user = $this->user();
        $this->patientRole($user);
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/treatments')->assertNotFound()->assertExactJson([
            'data' => null,
            'message' => 'No patient profile is associated with this account.',
            'errors' => null,
        ]);
    }

    public function test_scope_order_diagnosis_and_safe_prescriber_details(): void
    {
        [$user, $patient] = $this->patientUser();
        [, $other] = $this->patientUser();
        $prescriber = $this->professionalUser();
        $diagnosis = $this->diagnosis($patient, $prescriber);
        $older = $this->treatment($patient, $prescriber, '2026-06-01', 'ACTIVE', $diagnosis);
        $newer = $this->treatment($patient, $prescriber, '2026-07-01', 'FINISHED');
        $this->treatment($other, $prescriber, '2026-08-01');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/treatments')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->id)->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('data.1.diagnosis.description', 'Hypertension')
            ->assertJsonPath('data.1.prescriber.professional_type', 'DOCTOR')
            ->assertJsonPath('data.1.prescriber.full_name', 'Elena Morales')
            ->assertJsonPath('data.1.prescriber.specialty.name', 'Cardiology')
            ->assertJsonMissingPath('data.1.prescriber.email')
            ->assertJsonMissingPath('data.1.prescriber.professional_code')
            ->assertJsonMissingPath('data.1.created_by');
    }

    public function test_nullable_diagnosis_and_professional_profile_are_supported(): void
    {
        [$user, $patient] = $this->patientUser();
        $plainPrescriber = $this->user();
        $treatment = $this->treatment($patient, $plainPrescriber, '2026-07-01');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/treatments')->assertOk()
            ->assertJsonPath('data.0.id', $treatment->id)
            ->assertJsonPath('data.0.diagnosis', null)
            ->assertJsonPath('data.0.prescriber.professional_type', null)
            ->assertJsonPath('data.0.prescriber.specialty', null);
    }

    public function test_paginates_fifteen_and_preserves_filters(): void
    {
        [$user, $patient] = $this->patientUser();
        $prescriber = $this->user();
        foreach (range(1, 16) as $day) {
            $this->treatment($patient, $prescriber, sprintf('2026-07-%02d', $day));
        }
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/patient/treatments?status=ACTIVE')->assertOk()
            ->assertJsonCount(15, 'data')->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('links.next', fn ($v) => is_string($v) && str_contains($v, 'status=ACTIVE'));
    }

    public function test_status_filter_and_invalid_status(): void
    {
        [$user, $patient] = $this->patientUser();
        $prescriber = $this->user();
        $expected = $this->treatment($patient, $prescriber, '2026-07-01', 'SUSPENDED');
        $this->treatment($patient, $prescriber, '2026-07-02', 'FINISHED');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/treatments?status=SUSPENDED')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $expected->id);
        $this->getJson('/api/v1/patient/treatments?status=CANCELLED')->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_date_filters_and_invalid_range(): void
    {
        [$user, $patient] = $this->patientUser();
        $prescriber = $this->user();
        $this->treatment($patient, $prescriber, '2026-07-10');
        $middle = $this->treatment($patient, $prescriber, '2026-07-20');
        $this->treatment($patient, $prescriber, '2026-07-30');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/treatments?date_from=2026-07-20')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/patient/treatments?date_to=2026-07-20')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/patient/treatments?date_from=2026-07-15&date_to=2026-07-25')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $middle->id);
        $this->getJson('/api/v1/patient/treatments?date_from=2026-07-25&date_to=2026-07-20')
            ->assertUnprocessable()->assertJsonValidationErrors('date_to');
    }

    public function test_active_true_filters_and_false_does_not_filter(): void
    {
        [$user, $patient] = $this->patientUser();
        $prescriber = $this->user();
        $active = $this->treatment($patient, $prescriber, '2026-07-01');
        $this->treatment($patient, $prescriber, '2026-07-02', 'FINISHED');
        $this->treatment($patient, $prescriber, '2026-07-03', 'SUSPENDED');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/treatments?active=true')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);
        $this->getJson('/api/v1/patient/treatments?active=false')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_empty_collection_and_malicious_patient_id(): void
    {
        [$user, $patient] = $this->patientUser();
        [, $other] = $this->patientUser();
        $prescriber = $this->user();
        $this->treatment($other, $prescriber, '2026-07-01');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/patient/treatments?patient_id='.$other->id)->assertOk()
            ->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0)
            ->assertJsonStructure(['data', 'links', 'meta']);
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

    private function user(array $person = []): User
    {
        $personModel = Person::create(array_merge(['first_name' => fake()->firstName(),
            'first_last_name' => fake()->lastName(), 'date_of_birth' => '1980-01-01',
            'gender' => 'UNSPECIFIED'], $person));
        return User::create(['person_id' => $personModel->id, 'email' => fake()->unique()->safeEmail(),
            'password' => 'Password#1', 'status' => 'ACTIVE']);
    }

    private function professionalUser(): User
    {
        $user = $this->user(['first_name' => 'Elena', 'first_last_name' => 'Morales']);
        $specialty = Specialty::create(['name' => 'Cardiology', 'active' => true]);
        HealthStaff::create(['person_id' => $user->person_id, 'professional_type' => 'DOCTOR',
            'professional_code' => fake()->unique()->numerify('MED-######'),
            'specialty_id' => $specialty->id, 'active' => true]);
        return $user;
    }

    private function patientRole(User $user): void
    {
        $role = Role::firstOrCreate(['name' => 'PATIENT'], ['active' => true]);
        $user->roles()->attach($role->id, ['active' => true]);
        $user->unsetRelation('roles');
    }

    private function diagnosis(Patient $patient, User $doctor): Diagnosis
    {
        return Diagnosis::create(['patient_id' => $patient->id, 'cie_code' => 'I10',
            'description' => 'Hypertension', 'diagnosis_date' => '2026-05-01',
            'status' => 'ACTIVE', 'registered_by' => $doctor->id]);
    }

    private function treatment(Patient $patient, User $prescriber, string $start,
        string $status = 'ACTIVE', ?Diagnosis $diagnosis = null): Treatment
    {
        return Treatment::create(['patient_id' => $patient->id, 'diagnosis_id' => $diagnosis?->id,
            'indications' => 'Take as prescribed.', 'start_date' => $start, 'end_date' => null,
            'status' => $status, 'prescribed_by' => $prescriber->id]);
    }
}
