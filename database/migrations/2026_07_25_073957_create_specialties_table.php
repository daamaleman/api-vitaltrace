<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_specialties_table
 *
 * This anonymous migration class creates the `specialties` table used to
 * store professional specialties or areas of expertise. The table includes
 * audit fields for creation and update tracking.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `specialties` table with the following columns:
     * - id: primary key
     * - name: unique name of the specialty (max 100 chars)
     * - description: optional textual description (max 255 chars)
     * - active: boolean flag to indicate if the specialty is active
     * - created_at: timestamp set to current time on creation
     * - created_by: nullable user id who created the record
     * - updated_at: nullable timestamp of last update
     * - updated_by: nullable user id who last updated the record
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Core fields
            $table->string('name', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('active')->default(true);

            // Audit fields
            // created_at: set to current timestamp by default
            $table->timestamp('created_at')->useCurrent();
            // created_by: optional reference to the user who created the row
            $table->unsignedBigInteger('created_by')->nullable();

            // updated_at / updated_by: nullable fields for update tracking
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `specialties` table created in up().
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('specialties');
    }
};
