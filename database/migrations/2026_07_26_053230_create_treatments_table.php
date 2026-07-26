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
     * Creates the treatments table. A treatment may optionally relate to a
     * diagnosis and is prescribed by a doctor (RN-07).
     */
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();

            // Patient the treatment belongs to.
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();

            // Optional related diagnosis; kept if the diagnosis is removed.
            $table->foreignId('diagnosis_id')
                ->nullable()
                ->constrained('diagnoses')
                ->nullOnDelete();

            // Therapeutic plan / indications.
            $table->text('indications');

            // Validity period.
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Lifecycle status of the treatment.
            $table->enum('status', ['ACTIVE', 'FINISHED', 'SUSPENDED'])
                ->default('ACTIVE')
                ->index();

            // Doctor who prescribed the treatment (required).
            $table->unsignedBigInteger('prescribed_by');

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
        Schema::dropIfExists('treatments');
    }
};
