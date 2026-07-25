<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the patients table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create the patients table with a primary key and timestamps.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop the patients table if it exists.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
