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
     * Creates the clinical_evolutions table. Each row is an immutable point in
     * the patient's clinical timeline: the clinical status is kept as history and
     * never overwritten in a single field (RN-09).
     */
    public function up(): void
    {
        Schema::create('clinical_evolutions', function (Blueprint $table) {
            $table->id();

            // Patient the evolution belongs to.
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();

            // Authorized professional who registered the evolution (required).
            $table->unsignedBigInteger('registered_by');

            // Clinical summary of the evolution.
            $table->text('clinical_summary');

            // Clinical status at this point in time.
            $table->enum('status', [
                'STABLE',
                'OBSERVATION',
                'DELICATE',
                'CRITICAL',
                'RECOVERY',
            ])->index();

            // Clinical date and time.
            $table->dateTime('recorded_at')->index();

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
        Schema::dropIfExists('clinical_evolutions');
    }
};
