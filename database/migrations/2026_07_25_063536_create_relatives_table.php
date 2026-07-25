<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executes the migration to create the relatives table.
     * 
     * Creates the 'relatives' table with fields to manage records of related people,
     * including audit tracking and soft deletion functionality.
     */
    public function up(): void
    {
        Schema::create('relatives', function (Blueprint $table) {
            // Unique identifier for the table
            $table->id();

            // Relationship with the 'people' table - a person can have one relative
            // Unique: each person can only have one relative record
            // restrictOnDelete: prevents deletion of people who have relatives registered
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->restrictOnDelete();

            // Audit: creation date with default value (current timestamp)
            $table->timestamp('created_at')->useCurrent();
            // ID of the user who created the record
            $table->unsignedBigInteger('created_by')->nullable();

            // Audit: last update date (can be null)
            $table->timestamp('updated_at')->nullable();
            // ID of the user who last updated the record
            $table->unsignedBigInteger('updated_by')->nullable();

            // Soft deletion: marks records as deleted without removing data
            $table->softDeletes();
            // ID of the user who performed the soft deletion
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverts the migration by dropping the 'relatives' table.
     */
    public function down(): void
    {
        Schema::dropIfExists('relatives');
    }
};
