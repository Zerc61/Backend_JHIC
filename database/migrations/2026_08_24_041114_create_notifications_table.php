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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Notification content
            $table->string('title');
            $table->text('message');
            $table->string('type'); // booking_confirmed, order_status, payment_received, refund_processed, system_alert
            
            // Reference ke data terkait
            $table->morphs('notifiable'); // notifiable_type, notifiable_id
            
            // Status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // Channel
            $table->string('channel')->default('in_app'); // in_app, email, sms
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            
            // Metadata
            $table->json('data')->nullable();
            $table->json('action')->nullable(); // { "label": "Lihat Booking", "url": "/bookings/123" }
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('is_read');
            $table->index('type');
            $table->index('created_at');
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
