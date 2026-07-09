<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_plan_destinations', function (Blueprint $table) {
            $table->foreignId('trip_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('destination_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('day_number');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->primary(['trip_plan_id', 'destination_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_plan_destinations');
    }
};
