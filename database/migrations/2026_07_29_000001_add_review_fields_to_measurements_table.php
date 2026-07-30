<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->enum('review_status', ['PENDING', 'REVIEWED'])
                ->default('PENDING')
                ->index()
                ->after('observation');
            $table->dateTime('reviewed_at')->nullable()->after('review_status');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('review_observation')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'review_status',
                'reviewed_at',
                'reviewed_by',
                'review_observation',
            ]);
        });
    }
};
