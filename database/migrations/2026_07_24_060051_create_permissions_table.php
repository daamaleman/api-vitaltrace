<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the permissions table.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            // Unique permission identifier used by the application.
            $table->string('code', 80)->unique();
            // Human-readable permission name.
            $table->string('name', 120);
            // Optional permission details.
            $table->string('description', 255)->nullable();
            // Timestamp when the record was created.
            $table->timestamp('created_at')->useCurrent();
            // User ID that created the record.
            $table->unsignedBigInteger('created_by')->nullable();
            // Timestamp when the record was last updated.
            $table->timestamp('updated_at')->nullable();
            // User ID that last updated the record.
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    /**
     * Drop the permissions table.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
