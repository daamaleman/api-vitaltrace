<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeds the six system roles defined in the specification (section 2.1).
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'PATIENT', 'description' => 'Patient'],
            ['name' => 'RELATIVE', 'description' => 'Relative'],
            ['name' => 'DOCTOR', 'description' => 'Doctor'],
            ['name' => 'NURSE', 'description' => 'Nurse'],
            ['name' => 'ADMISSION', 'description' => 'Admission'],
            ['name' => 'SYSTEM_ADMIN', 'description' => 'System administrator'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}