<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOT NULL columns referencing users → restrictOnDelete.
     *
     * @var array<string, array<int, string>>
     */
    private array $restrictColumns = [
        'patients' => ['registered_by'],
        'patient_relative' => ['registered_by'],
        'professional_assignments' => ['assigned_by'],
        'correction_requests' => ['requested_by'],
        'diagnoses' => ['registered_by'],
        'clinical_evolutions' => ['registered_by'],
        'treatments' => ['prescribed_by'],
    ];

    /**
     * Nullable columns referencing users → nullOnDelete.
     *
     * @var array<string, array<int, string>>
     */
    private array $nullableColumns = [
        'roles' => ['created_by', 'updated_by'],
        'people' => ['created_by', 'updated_by', 'deleted_by'],
        'users' => ['created_by', 'updated_by', 'deleted_by'],
        'user_role' => ['assigned_by'],
        'permissions' => ['created_by', 'updated_by'],
        'role_permission' => ['created_by', 'updated_by'],
        'patients' => ['created_by', 'updated_by', 'deleted_by'],
        'relatives' => ['created_by', 'updated_by', 'deleted_by'],
        'patient_relative' => ['authorized_by', 'created_by', 'updated_by', 'deleted_by'],
        'administrative_staff' => ['created_by', 'updated_by', 'deleted_by'],
        'specialties' => ['created_by', 'updated_by'],
        'health_staff' => ['created_by', 'updated_by', 'deleted_by'],
        'professional_assignments' => ['created_by', 'updated_by', 'deleted_by'],
        'account_activations' => ['created_by', 'updated_by'],
        'correction_requests' => ['reviewed_by', 'created_by', 'updated_by'],
        'diagnoses' => ['created_by', 'updated_by', 'deleted_by'],
        'clinical_evolutions' => ['created_by', 'updated_by', 'deleted_by'],
        'treatments' => ['created_by', 'updated_by', 'deleted_by'],
    ];

    public function up(): void
    {
        $this->applyForeignKeys($this->restrictColumns, 'restrict');
        $this->applyForeignKeys($this->nullableColumns, 'null');
    }

    public function down(): void
    {
        $this->dropForeignKeys($this->restrictColumns);
        $this->dropForeignKeys($this->nullableColumns);
    }

    /**
     * @param  array<string, array<int, string>>  $map
     */
    private function applyForeignKeys(array $map, string $onDelete): void
    {
        foreach ($map as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns, $onDelete) {
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($tableName, $column)) {
                        continue;
                    }

                    $foreign = $table->foreign($column)->references('id')->on('users');

                    if ($onDelete === 'restrict') {
                        $foreign->restrictOnDelete();
                    } else {
                        $foreign->nullOnDelete();
                    }
                }
            });
        }
    }

    /**
     * @param  array<string, array<int, string>>  $map
     */
    private function dropForeignKeys(array $map): void
    {
        foreach ($map as $tableName => $columns) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropForeign([$column]);
                    }
                }
            });
        }
    }
};
