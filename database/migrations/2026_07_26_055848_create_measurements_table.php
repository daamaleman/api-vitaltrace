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
     * Creates the measurements table, storing patient measurements captured by
     * patients, relatives or clinical staff, together with their origin and author.
     */
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();

            // Patient the measurement belongs to.
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();

            // Measurement type from the catalog.
            $table->foreignId('measurement_type_id')->constrained('measurement_types')->restrictOnDelete();

            // Measured value.
            $table->decimal('value', 12, 3);

            // Unit as captured at measurement time.
            $table->string('unit', 30);

            // Measurement date and time.
            $table->dateTime('measured_at')->index();

            // Who originated the measurement.
            $table->enum('origin', ['PATIENT', 'RELATIVE', 'DOCTOR', 'NURSE'])->index();

            // User who authored the measurement (required).
            $table->unsignedBigInteger('author_user_id');

            // Optional note associated with the measurement.
            $table->text('observation')->nullable();

            // Audit columns.
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Composite index for patient timeline queries.
            $table->index(['patient_id', 'measured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
