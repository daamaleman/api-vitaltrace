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
     * Creates the measurement_types catalog table (e.g. blood pressure, glucose),
     * defining the base unit and decimal precision used by measurements.
     */
    public function up(): void
    {
        Schema::create('measurement_types', function (Blueprint $table) {
            $table->id();

            // Unique measurement type name.
            $table->string('name', 100)->unique();

            // Standard base unit for this measurement type.
            $table->string('base_unit', 30);

            // Decimal precision for stored values.
            $table->unsignedTinyInteger('decimals')->default(2);

            // Availability flag.
            $table->boolean('active')->default(true);

            // Audit columns.
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_types');
    }
};
