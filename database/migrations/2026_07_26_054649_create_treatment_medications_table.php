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
     * Creates the treatment_medication table, the prescription detail linking a
     * treatment with a catalog medication, including dose, route, frequency and
     * optional schedules.
     */
    public function up(): void
    {
        Schema::create('treatment_medication', function (Blueprint $table) {
            $table->id();

            // Parent treatment.
            $table->foreignId('treatment_id')->constrained('treatments')->restrictOnDelete();

            // Prescribed catalog medication.
            $table->foreignId('medication_id')->constrained('medications')->restrictOnDelete();

            // Prescribed dose.
            $table->string('dose', 80);

            // Administration route.
            $table->string('route', 50);

            // Administration frequency.
            $table->string('frequency', 80);

            // Optional variable schedules.
            $table->json('schedules')->nullable();

            // Optional specific validity period.
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

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
        Schema::dropIfExists('treatment_medication');
    }
};
