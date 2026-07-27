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
     * Creates the integration_logs table, an append-only technical log of
     * external integration calls. Rows are immutable: only created_at is kept,
     * with no audit columns, no soft deletes, no update and no delete.
     */
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Provider or integration name.
            $table->string('service', 80)->index();

            // Requested operation.
            $table->string('operation', 100);

            // Local record reference, if any.
            $table->string('local_reference', 150)->nullable();

            // Result status.
            $table->enum('status', ['PENDING', 'SUCCESS', 'ERROR'])->index();

            // Number of attempts.
            $table->unsignedTinyInteger('attempts')->default(0);

            // Summarized error, without secrets.
            $table->text('error_summary')->nullable();

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
        Schema::dropIfExists('integration_logs');
    }
};
