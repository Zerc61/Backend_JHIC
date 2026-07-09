<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->string('location')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['upcoming', 'ongoing', 'finished', 'cancelled'])->default('upcoming');
            $table->timestamps();

            $table->index('start_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
