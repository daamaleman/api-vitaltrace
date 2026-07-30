<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CorrectionRequest;
use App\Models\Patient;
use Illuminate\Database\Seeder;

/**
 * Seeds sample correction requests (as a patient would submit from the app),
 * so Admission has pending requests to review.
 */
class CorrectionRequestDemoSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::whereIn('record_number', ['VT-2026-001', 'VT-2026-002'])->get();

        $samples = [
            ['phone', '', '+505 8888 1234', 'My phone number was never registered.'],
            ['address', 'Managua', 'Masaya, Nicaragua', 'I moved to a new address.'],
        ];

        foreach ($patients as $index => $patient) {
            [$field, $current, $requested, $reason] = $samples[$index % count($samples)];

            CorrectionRequest::firstOrCreate(
                ['patient_id' => $patient->id, 'field' => $field],
                [
                    'current_value' => $current ?: 'N/A',
                    'requested_value' => $requested,
                    'reason' => $reason,
                    'status' => 'PENDING',
                ],
            );
        }

        $this->command->info('Correction requests seeded.');
    }
}