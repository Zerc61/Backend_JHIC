<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_package_schedules', function (Blueprint $table) {
            $table->string('pickup_location')->nullable()->after('notes');
            $table->time('pickup_time')->nullable()->after('pickup_location');
            $table->string('vehicle_info')->nullable()->after('pickup_time')->comment('Plat nomor, warna kendaraan, dll');
            $table->string('driver_name')->nullable()->after('vehicle_info');
            $table->string('driver_phone')->nullable()->after('driver_name');
        });
    }

    public function down(): void
    {
        Schema::table('travel_package_schedules', function (Blueprint $table) {
            $table->dropColumn(['pickup_location', 'pickup_time', 'vehicle_info', 'driver_name', 'driver_phone']);
        });
    }
};