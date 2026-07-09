<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->decimal('budget', 12, 2)->default(0);
            $table->unsignedInteger('duration_days')->default(1);
            $table->unsignedInteger('total_people')->default(1);
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->json('itinerary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_plans');
    }
};
