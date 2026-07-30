<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Medication;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Seeds demo data for the admin panel: specialties, medications and a set of
 * audit log entries so the admin views have content to display.
 */
class AdminDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Specialties (catalog).
        $specialties = ['Cardiology', 'Endocrinology', 'Internal Medicine', 'Nephrology', 'General Medicine'];
        foreach ($specialties as $name) {
            Specialty::firstOrCreate(['name' => $name], ['active' => true]);
        }

        // Medications (catalog).
        $medications = [
            ['Losartan', '50 mg tablet'],
            ['Metformin', '850 mg tablet'],
            ['Amlodipine', '5 mg tablet'],
            ['Insulin glargine', '100 U/mL injection'],
            ['Atorvastatin', '20 mg tablet'],
        ];
        foreach ($medications as [$name, $presentation]) {
            Medication::firstOrCreate(
                ['generic_name' => $name],
                ['presentation' => $presentation, 'active' => true],
            );
        }

        // Audit logs (sample activity).
        $admin = User::where('email', 'admin@vitaltrace.lat')->first();
        $doctor = User::where('email', 'doctor@vitaltrace.lat')->first();
        $admission = User::where('email', 'admission@vitaltrace.lat')->first();

        $entries = [
            [$admission, 'ADMISSION', 'CREATE', 'patients', 1, 'Registered patient VT-2026-001'],
            [$doctor, 'DOCTOR', 'CREATE', 'diagnoses', 1, 'Recorded hypertension diagnosis'],
            [$doctor, 'DOCTOR', 'UPDATE', 'alerts', 1, 'Classified alert'],
            [$admission, 'ADMISSION', 'CREATE', 'account_activations', 4, 'Issued activation code'],
            [$admin, 'SYSTEM_ADMIN', 'UPDATE', 'users', 3, 'Reset account password'],
            [$admission, 'ADMISSION', 'UPDATE', 'correction_requests', 1, 'Approved phone correction'],
        ];

        foreach ($entries as $index => [$user, $roleSnapshot, $action, $table, $recordId, $note]) {
            if ($user === null) {
                continue;
            }
            AuditLog::create([
                'user_id' => $user->id,
                'role_snapshot' => $roleSnapshot,
                'action' => $action,
                'table' => $table,
                'record_id' => $recordId,
                'old_values' => null,
                'new_values' => json_encode(['note' => $note]),
                'ip_address' => '190.0.0.'.(10 + $index),
                'user_agent' => 'VitalTrace Demo Client',
                'request_id' => (string) Str::uuid(),
                'created_at' => Carbon::now()->subHours($index * 2),
            ]);
        }

        $this->command->info('Admin demo data seeded: specialties, medications and audit logs.');
    }
}