<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the professional_assignments table, which links a patient with a
     * health professional (primary doctor, secondary doctor or nurse) and tracks
     * the validity period and status of each assignment.
     */
    public function up(): void
    {
        Schema::create('professional_assignments', function (Blueprint $table) {
            $table->id();

            // Patient receiving the assignment.
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();

            // Assigned health professional.
            $table->foreignId('health_staff_id')->constrained('health_staff')->restrictOnDelete();

            // Assignment responsibility type.
            $table->enum('assignment_type', [
                'PRIMARY_DOCTOR',
                'SECONDARY_DOCTOR',
                'NURSE',
            ])->index();

            // Validity period.
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Lifecycle status of the assignment.
            $table->enum('status', ['ACTIVE', 'FINISHED', 'SUSPENDED'])->default('ACTIVE')->index();

            // Justification for a change of assignment.
            $table->text('change_reason')->nullable();

            // Admission user who created the assignment (required).
            $table->unsignedBigInteger('assigned_by');

            // Audit columns.
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_assignments');
    }
};
