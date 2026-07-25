<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create the patient_relative table.
 * 
 * This migration establishes a junction table that manages the relationships between patients and their relatives,
 * including access permissions, relationship types, and lifecycle states.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the patient_relative table with columns for:
     * - Primary and foreign key relationships
     * - Relationship type and access scope
     * - Status tracking with enum values (PENDING, ACTIVE, REVOKED, EXPIRED)
     * - Date range tracking for access validity
     * - Audit fields (created_by, updated_by, deleted_by)
     * - Soft deletion support
     */
    public function up(): void
    {
        Schema::create('patient_relative', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys with cascade delete restrictions
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('relative_id')->constrained('relatives')->restrictOnDelete();

            // Relationship details
            $table->string('relationship', 50);
            $table->json('scope')->nullable();

            // Status tracking with indexing for query optimization
            $table->enum('status', [
                'PENDING',
                'ACTIVE',
                'REVOKED',
                'EXPIRED',
            ])->default('PENDING')->index();

            // Access period validity dates
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Audit fields for tracking authorization
            $table->unsignedBigInteger('registered_by');
            $table->unsignedBigInteger('authorized_by')->nullable();

            // Composite unique constraint to prevent duplicate relationships
            $table->unique(['patient_id', 'relative_id']);

            // Timestamp tracking for record creation
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            // Timestamp tracking for record updates
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Soft deletion support for data preservation
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Drops the patient_relative table if it exists.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_relative');
    }
};
