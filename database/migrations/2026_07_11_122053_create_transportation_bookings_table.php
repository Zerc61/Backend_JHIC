<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transportation_bookings')) {
            // MariaDB dapat menyisakan tabel ketika pembuatan FK sebelumnya gagal.
            // Pada kondisi itu, tambahkan FK yang belum terbentuk tanpa membuat tabel lagi.
            Schema::table('transportation_bookings', function (Blueprint $table) {
                $table->foreign('transportation_id')
                    ->references('id')
                    ->on('transportations')
                    ->restrictOnDelete();
            });

            return;
        }

        Schema::create('transportation_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('transportation_id')->constrained()->onDelete('restrict');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('number_of_days');
            $table->string('pickup_location')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'picked_up', 'returned', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportation_bookings');
    }
};
