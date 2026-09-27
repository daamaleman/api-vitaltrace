<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Support\Auditing\AuditLogger;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Deshabilitar la auditoría durante la ejecución de los seeders
        AuditLogger::disable();

        $this->call([
            RoleSeeder::class,
            TestUserSeeder::class,
        ]);
    }
}