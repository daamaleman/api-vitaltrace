<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to create the users table
 * 
 * This migration creates the users table with columns for authentication,
 * account status tracking, and audit trail information.
 */
return new class extends Migration
{
    /**
     * Run the migration to create the users table
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Primary key
            $table->id();

            // User reference to person
            $table->foreignId('person_id')
                ->unique()
                ->constrained('people')
                ->restrictOnDelete();

            // Authentication fields
            $table->string('email', 150)->unique();
            $table->string('password')->nullable();

            // Account status tracking
            $table->enum('status', [
                'PENDING',
                'ACTIVE',
                'BLOCKED',
                'SUSPENDED',
                'DEACTIVATED',
            ])->default('PENDING')->index();

            // Security and verification fields
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_access_at')->nullable();
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->timestamp('blocked_until')->nullable();

            // Remember me token for persistent login
            $table->rememberToken();

            // Audit trail - creation
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            // Audit trail - update
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Soft delete with audit trail
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migration to drop the users table
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
