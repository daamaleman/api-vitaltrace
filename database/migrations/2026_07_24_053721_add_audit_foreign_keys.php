<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $auditColumns = [
        'roles' => ['created_by', 'updated_by'],
        'people' => ['created_by', 'updated_by', 'deleted_by'],
        'users' => ['created_by', 'updated_by', 'deleted_by'],
    ];

    public function up(): void
    {
        foreach ($this->auditColumns as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->foreign($column)->references('id')->on('users')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->auditColumns as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->dropForeign([$column]);
                }
            });
        }
    }
};
