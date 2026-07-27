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
     * Creates the alert_history table, an append-only log of alert state changes.
     * Rows are immutable: only created_at is kept, with no update or delete.
     */
    public function up(): void
    {
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();

            // Alert this history entry belongs to.
            $table->foreignId('alert_id')->constrained('alerts')->restrictOnDelete();

            // Action performed on the alert.
            $table->string('action', 80)->index();

            // Previous status, if any.
            $table->string('previous_status', 40)->nullable();

            // Resulting status.
            $table->string('new_status', 40);

            // Justification for the transition.
            $table->text('comment')->nullable();

            // User responsible for the action (required).
            $table->unsignedBigInteger('user_id');

            // Immutable creation timestamp only.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_history');
    }
};
