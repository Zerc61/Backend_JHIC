<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Database yang sudah pernah di-migrate sebelum tabel transportasi
        // ditambahkan belum memiliki kolom ini.
        if (!Schema::hasColumn('travel_packages', 'transportation_id')) {
            Schema::table('travel_packages', function (Blueprint $table) {
                $table->foreignId('transportation_id')
                    ->nullable()
                    ->after('hotel_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        // Menyelaraskan enum lama dengan tipe booking yang dipakai controller.
        DB::statement("ALTER TABLE bookings MODIFY booking_type ENUM('hotel', 'transportation', 'transport_ticket', 'travel_package') NOT NULL");
    }

    public function down(): void
    {
        if (Schema::hasColumn('travel_packages', 'transportation_id')) {
            Schema::table('travel_packages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('transportation_id');
            });
        }

        DB::statement("ALTER TABLE bookings MODIFY booking_type ENUM('hotel', 'transport_ticket', 'travel_package') NOT NULL");
    }
};
