<?php
// database/migrations/2026_07_13_000001_create_transport_tickets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->enum('transport_mode', ['pesawat', 'kereta', 'bus', 'kapal']);
            $table->string('origin_code');
            $table->string('origin_name');
            $table->string('destination_code');
            $table->string('destination_name');
            $table->string('flight_number')->nullable();
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->boolean('is_transit')->default(false);
            $table->string('transit_info')->nullable();
            $table->string('class_type')->default('Ekonomi');
            $table->unsignedSmallInteger('available_seats')->default(0);
            $table->decimal('price_per_ticket', 12, 2);
            $table->enum('status', ['available', 'sold_out', 'expired'])->default('available');
            $table->dateTime('valid_until')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['origin_code', 'destination_code']);
            $table->index('departure_time');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_tickets');
    }
};
