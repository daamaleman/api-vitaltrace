<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     *
     * Creates the relatives table and its audit columns.
     */
    public function up(): void
    {
        Schema::create('relatives', function (Blueprint $table) {
            // Primary key.
            $table->id();

            // One-to-one relationship with people.
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->restrictOnDelete();

            // Creation audit fields.
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            // Update audit fields.
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Soft delete and deletion audit fields.
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migration.
     *
     * Drops the relatives table if it exists.
     */
    public function down(): void
    {
        Schema::dropIfExists('relatives');
    }
};
