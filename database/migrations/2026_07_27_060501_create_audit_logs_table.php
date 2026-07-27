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
     * Creates the audit_logs table, an append-only trail of user and system
     * actions. Rows are immutable: only created_at is kept, with no soft deletes,
     * no update and no delete. The user is nullable to allow system actions.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Acting user; nullable for system actions.
            $table->unsignedBigInteger('user_id')->nullable();

            // Role at the moment of the action.
            $table->string('role_snapshot', 50)->nullable();

            // Performed action.
            $table->enum('action', ['CREATE', 'UPDATE', 'DELETE', 'ACCESS', 'LOGIN', 'LOGOUT'])->index();

            // Affected table.
            $table->string('table', 100)->nullable()->index();

            // Affected record.
            $table->unsignedBigInteger('record_id')->nullable()->index();

            // Previous and new values.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request context.
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Correlation identifier.
            $table->uuid('request_id')->index();

            // Immutable creation timestamp only.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
