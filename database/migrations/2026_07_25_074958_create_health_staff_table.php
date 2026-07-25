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
     * Creates the health_staff table, which holds the professional profile
     * (doctor or nurse) linked to a person. The specialty is optional and,
     * when present, must reference an existing catalog entry.
     */
    public function up(): void
    {
        Schema::create('health_staff', function (Blueprint $table) {
            $table->id();

            // One-to-one link with the person identity record.
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->restrictOnDelete();

            // Professional classification: doctor or nurse.
            $table->enum('professional_type', ['DOCTOR', 'NURSE'])->index();

            // Institutional professional license/registration code.
            $table->string('professional_code', 50)->unique();

            // Optional main specialty; restricted on delete to protect the catalog.
            $table->foreignId('specialty_id')
                ->nullable()
                ->constrained('specialties')
                ->restrictOnDelete();

            // Employment status flag.
            $table->boolean('active')->default(true);

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
        Schema::dropIfExists('health_staff');
    }
};
