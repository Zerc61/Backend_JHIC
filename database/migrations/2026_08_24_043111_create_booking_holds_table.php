<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            
            // Item being held
            $table->string('holdable_type'); // HotelRoom, TransportationSlot, PackageSchedule
            $table->unsignedBigInteger('holdable_id');
            
            // Hold details
            $table->integer('quantity')->default(1);
            $table->timestamp('held_at');
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            
            // Status
            $table->string('status')->default('active'); // active, released, expired
            $table->string('release_reason')->nullable(); // payment_completed, payment_failed, manual, expired
            
            // Tracking
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('booking_id');
            $table->index('expires_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_holds');
    }
};
