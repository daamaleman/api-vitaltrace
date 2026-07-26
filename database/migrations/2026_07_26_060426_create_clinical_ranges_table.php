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
     * Creates the clinical_ranges table. A range may be patient-specific or, when
     * patient_id is null, a general range for a measurement type. Ranges feed the
     * alert engine (RF-BE-13).
     */
    public function up(): void
    {
        Schema::create('clinical_ranges', function (Blueprint $table) {
            $table->id();

            // Target patient; null means a general range for the measurement type.
            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('patients')
                ->nullOnDelete();

            // Measurement type the range applies to.
            $table->foreignId('measurement_type_id')->constrained('measurement_types')->restrictOnDelete();

            // Lower and upper bounds; either may be open (null).
            $table->decimal('min_value', 12, 3)->nullable();
            $table->decimal('max_value', 12, 3)->nullable();

            // Severity triggered when a value falls outside the range.
            $table->enum('severity', ['INFORMATIONAL', 'MODERATE', 'HIGH', 'CRITICAL'])->index();

            // Validity period.
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Doctor who defined the range (required).
            $table->unsignedBigInteger('defined_by');

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
        Schema::dropIfExists('clinical_ranges');
    }
};
