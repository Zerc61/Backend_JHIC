<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_id')->constrained('itinerary_days')->cascadeOnDelete();
            $table->string('slot'); // morning, afternoon, evening
            $table->string('type'); // hotel, destination, umkm, transport, custom
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('custom_name')->nullable();
            $table->text('custom_note')->nullable();
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->timestamps();

            $table->index(['day_id', 'slot', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_items');
    }
};