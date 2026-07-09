<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reviewable_type'); // Destination, Umkm, Product
            $table->unsignedBigInteger('reviewable_id');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['reviewable_type', 'reviewable_id']);
            $table->unique(['user_id', 'reviewable_type', 'reviewable_id'], 'unique_user_review');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
