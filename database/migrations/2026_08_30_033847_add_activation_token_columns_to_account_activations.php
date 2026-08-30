<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_activations', function (Blueprint $table) {
            if (!Schema::hasColumn('account_activations', 'activation_token_hash')) {
                $table->string('activation_token_hash', 64)->nullable()->after('status');
            }
            if (!Schema::hasColumn('account_activations', 'activation_token_expires_at')) {
                $table->timestamp('activation_token_expires_at')->nullable()->after('activation_token_hash');
            }
            if (!Schema::hasColumn('account_activations', 'activation_token_used_at')) {
                $table->timestamp('activation_token_used_at')->nullable()->after('activation_token_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_activations', function (Blueprint $table) {
            $table->dropColumn(['activation_token_hash', 'activation_token_expires_at', 'activation_token_used_at']);
        });
    }
};
