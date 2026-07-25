<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the administrative_staff table.
 *
 * This table stores administrative staff profiles linked 1:1 to a person,
 * including role type, job position, active status, audit fields, and soft delete metadata.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::create('administrative_staff', function (Blueprint $table) {
            // Primary key.
            $table->id();

            // One-to-one relationship with people table.
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->restrictOnDelete();

            // Unique internal employee identifier.
            $table->string('employee_code', 30)->unique();

            // Administrative staff category.
            $table->enum('type', ['ADMISSION', 'SYSTEM_ADMIN']);

            // Job title/position and active status flag.
            $table->string('position', 100);
            $table->boolean('active')->default(true);

            // Creation audit fields.
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            // Update audit fields.
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Soft delete timestamp and deletion audit field.
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrative_staff');
    }
};
