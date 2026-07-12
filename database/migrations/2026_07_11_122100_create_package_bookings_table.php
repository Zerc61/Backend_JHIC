<?php
// database/migrations/2026_07_11_122319_create_package_booking_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('travel_package_id')->constrained()->restrictOnDelete();
            $table->foreignId('schedule_id')->constrained('travel_package_schedules')->restrictOnDelete();
            $table->unsignedSmallInteger('total_travelers');
            $table->json('traveler_names');
            $table->string('contact_person');
            $table->string('contact_phone', 20);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_bookings');
    }
};
