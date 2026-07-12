<?php

// database/migrations/2026_07_13_000002_create_transport_ticket_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_ticket_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('transport_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('passenger_name');
            $table->enum('passenger_id_type', ['KTP', 'Passport', 'SIM'])->default('KTP');
            $table->string('passenger_id_number');
            $table->string('seat_number')->nullable();
            $table->string('ticket_number')->nullable();
            $table->string('provider_booking_code')->nullable();
            $table->string('qr_code')->nullable();
            $table->enum('status', ['confirmed', 'issued', 'cancelled'])->default('confirmed');
            $table->timestamp('issued_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_ticket_bookings');
    }
};
