<?php
// database/migrations/2026_07_11_122038_create_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('booking_type', ['hotel', 'transportation', 'transport_ticket', 'travel_package']);
            $table->enum('status', ['pending', 'paid', 'confirmed', 'completed', 'cancelled', 'refunded'])->default('pending');
            $table->decimal('total_price', 12, 2);
            $table->decimal('coin_amount', 16, 4);
            $table->decimal('coin_to_rupiah_rate', 15, 2)->default(2000.00);
            $table->decimal('rupiah_equivalent', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'booking_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
