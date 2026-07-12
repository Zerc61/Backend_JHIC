<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transportations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['car', 'motorcycle', 'bus', 'boat', 'other'])->default('car');
            $table->text('description');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->decimal('price_per_day', 12, 2);
            $table->boolean('includes_driver')->default(false);
            $table->boolean('includes_fuel')->default(false);
            $table->string('thumbnail')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['published', 'draft', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transportations');
    }
};
