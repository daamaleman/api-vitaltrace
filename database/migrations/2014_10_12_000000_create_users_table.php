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
     * 
     * Creates a users table with the following structure:
     * - id: Primary key
     * - person_id: Foreign key to people table
     * - email: Unique email address for authentication
     * - password: Hashed password field
     * - status: Account status (PENDING, ACTIVE, BLOCKED, SUSPENDED, DEACTIVATED)
     * - email_verified_at: Timestamp when email was verified
     * - last_access_at: Timestamp of the last user access
     * - failed_attempts: Counter for failed login attempts
     * - blocked_until: Timestamp when account will be unblocked
     * - created_at: Account creation timestamp
     * - created_by: Foreign key to the user who created this account
     * - updated_at: Last update timestamp
     * - updated_by: Foreign key to the user who last updated this account
     * - deleted_at: Soft delete timestamp
     * - deleted_by: Foreign key to the user who deleted this account
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
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Audit trail - update
            $table->timestamp('updated_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Soft delete with audit trail
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
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
