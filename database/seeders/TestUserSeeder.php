<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds one active test account per web-facing role (Doctor, Admission,
 * System Admin) so the login flow can be exercised end to end.
 */
class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['DOCTOR', 'doctor@vitaltrace.lat', 'Carlos', 'Ruiz'],
            ['ADMISSION', 'admission@vitaltrace.lat', 'Ana', 'Lopez'],
            ['SYSTEM_ADMIN', 'admin@vitaltrace.lat', 'Root', 'Admin'],
        ];

        foreach ($accounts as [$roleName, $email, $firstName, $lastName]) {
            $person = Person::firstOrCreate(
                ['first_name' => $firstName, 'first_last_name' => $lastName],
                [
                    'date_of_birth' => '1985-01-01',
                    'gender' => 'UNSPECIFIED',
                ],
            );

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'person_id' => $person->id,
                    'password' => Hash::make('Password123!'),
                    'status' => 'ACTIVE',
                    'email_verified_at' => now(),
                ],
            );

            $role = Role::where('name', $roleName)->first();

            if ($role !== null) {
                $user->roles()->syncWithoutDetaching([
                    $role->id => ['active' => true, 'assigned_at' => now()],
                ]);
            }
        }
    }
}