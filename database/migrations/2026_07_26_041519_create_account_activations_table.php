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
     * Creates the account_activations table, which stores the hashed activation
     * code sent by email, its expiration, usage and failed-attempt tracking.
     * The plain code is never persisted (RN-10).
     */
    public function up(): void
    {
        Schema::create('account_activations', function (Blueprint $table) {
            $table->id();

            // Account being activated.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // Secure hash of the six-digit code; the plain value is never stored.
            $table->string('code_hash');

            // Audited destination email address.
            $table->string('sent_to_email', 150);

            // Expiration timestamp (initial validity of 24 hours).
            $table->timestamp('expires_at')->index();

            // Successful usage timestamp.
            $table->timestamp('used_at')->nullable();

            // Number of failed attempts (max five before invalidation).
            $table->unsignedTinyInteger('attempts')->default(0);

            // Lifecycle status of the activation code.
            $table->enum('status', ['PENDING', 'USED', 'EXPIRED', 'INVALIDATED'])
                ->default('PENDING')
                ->index();

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
        Schema::dropIfExists('account_activations');
    }
};
