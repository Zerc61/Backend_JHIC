<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah 'destination_ticket' ke enum booking_type (MySQL only, SQLite skip)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_type ENUM('hotel','transportation','transport_ticket','travel_package','destination_ticket') NOT NULL");
        }

        Schema::create('destination_ticket_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('destination_id')->constrained('destinations');
            $table->date('visit_date');
            $table->unsignedSmallInteger('number_of_visitors');
            $table->json('visitor_names');
            $table->string('contact_person');
            $table->string('contact_phone');
            $table->string('qr_code')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'used', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index('visit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_ticket_bookings');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN booking_type ENUM('hotel','transportation','transport_ticket','travel_package') NOT NULL");
        }
    }
};