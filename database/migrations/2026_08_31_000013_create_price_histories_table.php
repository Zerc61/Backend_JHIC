<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_item_id')->constrained('wishlist_items')->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['wishlist_item_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};