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
     * Creates the appointments table. Appointments are managed internally and do
     * not depend on external services (RF-BE-15); external_sync tracks optional
     * calendar synchronization state.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // Patient the appointment belongs to.
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();

            // Professional attending the appointment.
            $table->foreignId('health_staff_id')->constrained('health_staff')->restrictOnDelete();

            // Scheduled date and time.
            $table->dateTime('scheduled_at')->index();

            // Duration in minutes.
            $table->unsignedSmallInteger('duration_minutes')->default(30);

            // General reason for the appointment.
            $table->string('reason', 255);

            // Lifecycle status.
            $table->enum('status', [
                'SCHEDULED',
                'CONFIRMED',
                'ATTENDED',
                'CANCELLED',
                'NO_SHOW',
            ])->default('SCHEDULED')->index();

            // Optional external calendar synchronization state.
            $table->enum('external_sync', [
                'NOT_APPLICABLE',
                'PENDING',
                'SYNCED',
                'ERROR',
            ])->default('NOT_APPLICABLE');

            // Audit columns.
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Composite index for patient calendar queries.
            $table->index(['patient_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
