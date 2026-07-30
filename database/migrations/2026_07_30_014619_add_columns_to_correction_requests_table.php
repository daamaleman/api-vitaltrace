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
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->foreignId('patient_id')->after('id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->after('patient_id')->constrained('users')->nullOnDelete();
            $table->string('field', 100)->after('requested_by');
            $table->string('current_value', 255)->after('field');
            $table->string('requested_value', 255)->after('current_value');
            $table->text('reason')->after('requested_value');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING')->after('reason');
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->text('response')->nullable()->after('reviewed_by');
            $table->timestamp('reviewed_at')->nullable()->after('response');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patient_id');
            $table->dropConstrainedForeignId('requested_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'field', 'current_value', 'requested_value', 'reason',
                'status', 'response', 'reviewed_at', 'created_by', 'updated_by',
            ]);
        });
    }
};
