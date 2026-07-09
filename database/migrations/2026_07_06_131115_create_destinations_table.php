<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_category_id')->constrained()->onDelete('restrict');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->time('open_hour')->nullable();
            $table->time('close_hour')->nullable();
            $table->decimal('ticket_price', 10, 2)->default(0);
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->enum('status', ['published', 'draft', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinations');
    }
};
