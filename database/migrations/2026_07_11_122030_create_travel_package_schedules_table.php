<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_package_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_package_id')->constrained()->onDelete('cascade');
            $table->date('departure_date');
            $table->date('return_date');
            $table->unsignedSmallInteger('max_capacity');
            $table->unsignedSmallInteger('current_booked')->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['available', 'full', 'cancelled'])->default('available');
            $table->timestamps();

            $table->unique(['travel_package_id', 'departure_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_package_schedules');
    }
};