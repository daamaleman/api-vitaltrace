<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('password_set_at')->nullable()->after('password')->index();
        });

        Schema::table('account_activations', function (Blueprint $table): void {
            $table->string('activation_token_hash', 64)->nullable()->after('code_hash')->unique();
            $table->timestamp('activation_token_expires_at')->nullable()->after('activation_token_hash')->index();
            $table->timestamp('activation_token_used_at')->nullable()->after('activation_token_expires_at');
        });

        DB::table('users')
            ->where('status', 'ACTIVE')
            ->whereNotNull('password')
            ->update(['password_set_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('account_activations', function (Blueprint $table): void {
            $table->dropUnique(['activation_token_hash']);
            $table->dropIndex(['activation_token_expires_at']);
            $table->dropColumn([
                'activation_token_hash',
                'activation_token_expires_at',
                'activation_token_used_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['password_set_at']);
            $table->dropColumn('password_set_at');
        });
    }
};
