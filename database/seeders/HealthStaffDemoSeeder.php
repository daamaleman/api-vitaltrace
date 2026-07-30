<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HealthStaff;
use App\Models\Person;
use Illuminate\Database\Seeder;

/**
 * Seeds additional health staff (a second doctor and a nurse) so that
 * Admission has real professionals to assign to patients.
 */
class HealthStaffDemoSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            ['DOCTOR', 'MD-0002', 'Elena', 'Morales'],
            ['DOCTOR', 'MD-0003', 'Ricardo', 'Vega'],
            ['NURSE', 'RN-0001', 'Sofia', 'Herrera'],
            ['NURSE', 'RN-0002', 'Diego', 'Castro'],
        ];

        foreach ($staff as [$type, $code, $firstName, $lastName]) {
            $person = Person::firstOrCreate(
                ['first_name' => $firstName, 'first_last_name' => $lastName],
                [
                    'date_of_birth' => '1980-01-01',
                    'gender' => 'UNSPECIFIED',
                ],
            );

            HealthStaff::firstOrCreate(
                ['professional_code' => $code],
                [
                    'person_id' => $person->id,
                    'professional_type' => $type,
                    'specialty_id' => null,
                    'active' => true,
                ],
            );
        }

        $this->command->info('Health staff seeded: 2 doctors and 2 nurses.');
    }
}