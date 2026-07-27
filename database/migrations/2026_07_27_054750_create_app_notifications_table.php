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
     * Creates the notifications table for internal and email notifications. The
     * message body avoids unnecessary clinical data; errors are stored as a
     * summary without secrets.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Recipient account (required).
            $table->unsignedBigInteger('user_id');

            // Notification type.
            $table->string('type', 80)->index();

            // Delivery channel.
            $table->enum('channel', ['INTERNAL', 'EMAIL']);

            // Subject line.
            $table->string('subject', 160);

            // General message without unnecessary clinical data.
            $table->text('general_message');

            // Delivery status.
            $table->enum('status', ['PENDING', 'SENT', 'ERROR', 'CANCELLED'])
                ->default('PENDING')
                ->index();

            // Retry counter.
            $table->unsignedTinyInteger('attempts')->default(0);

            // Scheduling and delivery timestamps.
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();

            // Error summary without secrets.
            $table->text('error_summary')->nullable();

            // Audit columns.
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
