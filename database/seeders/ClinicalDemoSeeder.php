<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\HealthStaff;
use App\Models\Measurement;
use App\Models\MeasurementType;
use App\Models\Patient;
use App\Models\Person;
use App\Models\ProfessionalAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds a realistic clinical demo dataset:
 * a doctor profile, assigned patients, measurement types, out-of-range
 * measurements and alerts in different states, so the doctor dashboard
 * has meaningful content to display.
 */
class ClinicalDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure the demo doctor has a health_staff profile.
        $doctorUser = User::where('email', 'doctor@vitaltrace.lat')->first();

        if ($doctorUser === null) {
            $this->command->warn('Doctor user not found. Run TestUserSeeder first.');
            return;
        }

        $doctorStaff = HealthStaff::firstOrCreate(
            ['person_id' => $doctorUser->person_id],
            [
                'professional_type' => 'DOCTOR',
                'professional_code' => 'MD-0001',
                'active' => true,
            ],
        );

        // 2. Measurement types (catalog).
        $bloodPressure = MeasurementType::firstOrCreate(
            ['name' => 'Systolic blood pressure'],
            ['base_unit' => 'mmHg', 'decimals' => 0, 'active' => true],
        );

        $glucose = MeasurementType::firstOrCreate(
            ['name' => 'Blood glucose'],
            ['base_unit' => 'mg/dL', 'decimals' => 0, 'active' => true],
        );

        $oxygen = MeasurementType::firstOrCreate(
            ['name' => 'Oxygen saturation'],
            ['base_unit' => '%', 'decimals' => 0, 'active' => true],
        );

        // 3. Create demo patients assigned to the doctor.
        $patientsData = [
            ['Maria', 'Gonzalez', 'VT-2026-001', $bloodPressure, 168, 'mmHg', 'HIGH', 'NEW', 'High blood pressure reading detected.'],
            ['Jose', 'Martinez', 'VT-2026-002', $glucose, 245, 'mg/dL', 'CRITICAL', 'NEW', 'Critical glucose level recorded.'],
            ['Carmen', 'Flores', 'VT-2026-003', $oxygen, 91, '%', 'MODERATE', 'CLASSIFIED', 'Oxygen saturation below expected range.'],
            ['Pedro', 'Ramirez', 'VT-2026-004', $bloodPressure, 152, 'mmHg', 'MODERATE', 'IN_PROGRESS', 'Elevated blood pressure under review.'],
            ['Lucia', 'Torres', 'VT-2026-005', $glucose, 118, 'mg/dL', 'INFORMATIONAL', 'CLOSED', 'Glucose slightly above target, resolved.'],
        ];

        foreach ($patientsData as $index => [$firstName, $lastName, $record, $type, $value, $unit, $severity, $alertStatus, $description]) {
            // Person + patient.
            $person = Person::firstOrCreate(
                ['first_name' => $firstName, 'first_last_name' => $lastName],
                [
                    'date_of_birth' => '1970-01-01',
                    'gender' => 'UNSPECIFIED',
                ],
            );

            $patient = Patient::firstOrCreate(
                ['record_number' => $record],
                [
                    'person_id' => $person->id,
                    'admission_date' => Carbon::now()->subDays(30 - $index),
                    'administrative_status' => 'ACTIVE',
                    'registered_by' => $doctorUser->id,
                ],
            );

            // Professional assignment (doctor as primary).
            ProfessionalAssignment::firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'health_staff_id' => $doctorStaff->id,
                    'assignment_type' => 'PRIMARY_DOCTOR',
                ],
                [
                    'start_date' => Carbon::now()->subDays(30),
                    'status' => 'ACTIVE',
                    'assigned_by' => $doctorUser->id,
                ],
            );

            // Measurement that triggered the alert.
            $measurement = Measurement::create([
                'patient_id' => $patient->id,
                'measurement_type_id' => $type->id,
                'value' => $value,
                'unit' => $unit,
                'measured_at' => Carbon::now()->subHours(($index + 1) * 3),
                'origin' => 'PATIENT',
                'author_user_id' => $doctorUser->id,
                'observation' => null,
            ]);

            // Alert linked to that measurement.
            Alert::create([
                'patient_id' => $patient->id,
                'measurement_id' => $measurement->id,
                'type' => 'OUT_OF_RANGE_MEASUREMENT',
                'severity' => $severity,
                'status' => $alertStatus,
                'description' => $description,
                'generated_at' => Carbon::now()->subHours(($index + 1) * 3),
                'closed_at' => $alertStatus === 'CLOSED' ? Carbon::now()->subHour() : null,
            ]);
        }

        // 4. Clinical follow-up data for the first two patients.
        $followUpPatients = Patient::whereIn('record_number', ['VT-2026-001', 'VT-2026-002'])->get();

        foreach ($followUpPatients as $patient) {
            // Diagnosis.
            $diagnosis = \App\Models\Diagnosis::firstOrCreate(
                ['patient_id' => $patient->id, 'cie_code' => 'I10'],
                [
                    'description' => 'Essential (primary) hypertension.',
                    'diagnosis_date' => Carbon::now()->subDays(25),
                    'status' => 'ACTIVE',
                    'registered_by' => $doctorUser->id,
                ],
            );
            
            // Treatment linked to the diagnosis.
            \App\Models\Treatment::firstOrCreate(
                ['patient_id' => $patient->id, 'diagnosis_id' => $diagnosis->id],
                [
                    'indications' => 'Low-sodium diet, daily monitoring, prescribed medication as directed.',
                    'start_date' => Carbon::now()->subDays(24),
                    'end_date' => null,
                    'status' => 'ACTIVE',
                    'prescribed_by' => $doctorUser->id,
                ],
            );
            
            // Clinical evolutions (history, not overwritten — RN-09).
            $evolutions = [
                ['Patient stable, blood pressure responding to treatment.', 'STABLE', 20],
                ['Slight elevation observed, kept under observation.', 'OBSERVATION', 10],
                ['Values improving, continuing current plan.', 'RECOVERY', 3],
            ];

            foreach ($evolutions as [$summary, $status, $daysAgo]) {
                \App\Models\ClinicalEvolution::create([
                    'patient_id' => $patient->id,
                    'registered_by' => $doctorUser->id,
                    'clinical_summary' => $summary,
                    'status' => $status,
                    'recorded_at' => Carbon::now()->subDays($daysAgo),
                ]);
            }
            
            // Extra historical measurements (trend).
            $bp = MeasurementType::where('name', 'Systolic blood pressure')->first();
            if ($bp !== null) {
                $readings = [
                    [162, 22], [158, 18], [150, 14], [145, 9], [138, 4],
                ];
                foreach ($readings as [$value, $daysAgo]) {
                    Measurement::create([
                        'patient_id' => $patient->id,
                        'measurement_type_id' => $bp->id,
                        'value' => $value,
                        'unit' => 'mmHg',
                        'measured_at' => Carbon::now()->subDays($daysAgo),
                        'origin' => 'PATIENT',
                        'author_user_id' => $doctorUser->id,
                        'observation' => null,
                    ]);
                }
            }
        }

        // 5. Appointments for assigned patients with the doctor.
        $allAssigned = Patient::whereIn('record_number', [
            'VT-2026-001', 'VT-2026-002', 'VT-2026-003', 'VT-2026-004',
        ])->get();

        $appointmentPlan = [
            [2, 30, 'Blood pressure follow-up', 'SCHEDULED'],
            [5, 30, 'Glucose control review', 'CONFIRMED'],
            [-7, 30, 'Routine check-up', 'ATTENDED'],
            [-3, 30, 'Medication adjustment', 'NO_SHOW'],
        ];

        foreach ($allAssigned as $index => $patient) {
            [$dayOffset, $duration, $reason, $status] = $appointmentPlan[$index % count($appointmentPlan)];

            \App\Models\Appointment::firstOrCreate(
                [
                    'patient_id' => $patient->id,
                    'health_staff_id' => $doctorStaff->id,
                    'reason' => $reason,
                ],
                [
                    'scheduled_at' => Carbon::now()->addDays($dayOffset)->setTime(9 + $index, 0),
                    'duration_minutes' => $duration,
                    'status' => $status,
                    'external_sync' => 'NOT_APPLICABLE',
                ],
            );
        }

        $this->command->info('Clinical demo data seeded: 5 patients, measurements and alerts.');
    }
}