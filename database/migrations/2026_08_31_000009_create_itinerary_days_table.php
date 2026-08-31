<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_plan_id')->constrained('trip_plans')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['trip_plan_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_days');
    }
};