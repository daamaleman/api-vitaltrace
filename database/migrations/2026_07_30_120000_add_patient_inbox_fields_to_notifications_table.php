<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->timestamp('read_at')->nullable()->after('sent_at')->index();
            $table->string('related_type', 80)->nullable()->after('read_at')->index();
            $table->unsignedBigInteger('related_id')->nullable()->after('related_type');
            $table->string('action_route', 120)->nullable()->after('related_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex(['read_at']);
            $table->dropIndex(['related_type']);
            $table->dropColumn(['read_at', 'related_type', 'related_id', 'action_route']);
        });
    }
};
