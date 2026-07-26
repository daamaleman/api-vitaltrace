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
     * Creates the diagnoses table. The CIE code is stored as an indexed string
     * reference, without duplicating the external catalog. Only doctors register
     * diagnoses (RN-07).
     */
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();

            // Patient the diagnosis belongs to.
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();

            // CIE code reference; nullable and indexed, not a local catalog copy.
            $table->string('cie_code', 20)->nullable()->index();

            // Clinical description.
            $table->text('description');

            // Diagnosis date.
            $table->date('diagnosis_date')->index();

            // Clinical status of the diagnosis.
            $table->enum('status', ['ACTIVE', 'RESOLVED', 'UNDER_REVIEW'])
                ->default('ACTIVE')
                ->index();

            // Doctor who registered the diagnosis (required).
            $table->unsignedBigInteger('registered_by');

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
        Schema::dropIfExists('diagnoses');
    }
};
