<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create the pivot table that links roles and permissions.
        Schema::create('role_permission', function (Blueprint $table) {
            // Primary key.
            $table->id();

            // Foreign key to the roles table.
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            // Foreign key to the permissions table.
            $table->foreignId('permission_id')->constrained('permissions')->restrictOnDelete();

            // Audit fields.
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Prevent duplicate role-permission pairs.
            $table->unique(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        // Drop the pivot table if it exists.
        Schema::dropIfExists('role_permission');
    }
};
