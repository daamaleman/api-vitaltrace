<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('code_hash');
            $table->string('sent_to_email', 150);
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->enum('status', ['PENDING', 'USED', 'EXPIRED', 'INVALIDATED'])->default('PENDING')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};
