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
     * Creates the alerts table. An alert is a non-diagnostic follow-up signal
     * (RN-11) that may originate from a measurement and requires professional review.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            // Patient the alert refers to.
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();

            // Originating measurement, if any; kept if the measurement is removed.
            $table->foreignId('measurement_id')
                ->nullable()
                ->constrained('measurements')
                ->nullOnDelete();

            // Event type.
            $table->string('type', 80)->index();

            // Priority severity.
            $table->enum('severity', ['INFORMATIONAL', 'MODERATE', 'HIGH', 'CRITICAL'])->index();

            // Lifecycle status.
            $table->enum('status', ['NEW', 'CLASSIFIED', 'ESCALATED', 'IN_PROGRESS', 'CLOSED'])
                ->default('NEW')
                ->index();

            // Non-diagnostic reason (RN-11).
            $table->text('description');

            // Generation and closing timestamps.
            $table->dateTime('generated_at')->index();
            $table->dateTime('closed_at')->nullable();

            // Audit columns.
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Composite index for patient alert queries.
            $table->index(['patient_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
