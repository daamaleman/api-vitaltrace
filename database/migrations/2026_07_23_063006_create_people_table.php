<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 80);
            $table->string('middle_name', 80)->nullable();
            $table->string('first_last_name', 80);
            $table->string('second_last_name', 80)->nullable();
            $table->date('date_of_birth');
            $table->string('gender', 30);
            $table->string('identity_document', 40)->nullable()->unique();
            $table->string('phone', 25)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
