<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create the patients table.
 * 
 * This migration creates the 'patients' table which stores patient information
 * linked to people records. It includes admission details, administrative status,
 * emergency contact information, and audit tracking fields.
 */
return new class extends Migration
{
    /**
     * Run the migration to create the patients table.
     * 
     * The table structure includes:
     * - Primary key (id)
     * - Foreign key reference to the people table (person_id)
     * - Medical record number (record_number) with unique constraint
     * - Admission date
     * - Administrative status enum with predefined states
     * - Emergency contact details (name and phone)
     * - Administrative notes field
     * - Audit fields for tracking creation, updates, and soft deletes
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign key to people table with unique constraint
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->restrictOnDelete();

            // Medical record number (unique identifier within the system)
            $table->string('record_number', 30)->unique();
            // Date when patient was admitted
            $table->date('admission_date');

            // Administrative status of the patient (enum with predefined values)
            $table->enum('administrative_status', [
                'PRE_REGISTERED',    // Patient has been pre-registered
                'ACTIVE',            // Patient is currently active
                'INACTIVE',          // Patient is inactive
                'DISCHARGED',        // Patient has been discharged
                'ARCHIVED',          // Patient record is archived
            ])->default('PRE_REGISTERED')->index();

            // Emergency contact information
            $table->string('emergency_contact_name', 160)->nullable();
            $table->string('emergency_contact_phone', 25)->nullable();
            // Additional administrative notes
            $table->text('administrative_notes')->nullable();

            // User ID of the person who registered the patient
            $table->unsignedBigInteger('registered_by')->nullable();

            // Audit timestamps and user tracking
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Soft delete support (for logical deletion without removing data)
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migration by dropping the patients table.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
