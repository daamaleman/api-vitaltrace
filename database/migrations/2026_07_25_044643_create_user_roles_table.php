<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_role', function (Blueprint $table) {
            // Primary key for the user-role pivot record.
            $table->id();

            // Reference to the user assigned to the role.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            // Reference to the role assigned to the user.
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();

            // Indicates whether the assignment is currently active.
            $table->boolean('active')->default(true);
            // Timestamp when the role was assigned.
            $table->timestamp('assigned_at')->useCurrent();
            // Timestamp when the role was revoked, if applicable.
            $table->timestamp('revoked_at')->nullable();

            // ID of the administrator or process that assigned the role.
            $table->unsignedBigInteger('assigned_by')->nullable();

            // Prevent duplicate assignments for the same user and role.
            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role');
    }
};
