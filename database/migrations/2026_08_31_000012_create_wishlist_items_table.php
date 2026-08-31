<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('wishlist_collections')->cascadeOnDelete();
            $table->string('wishlistable_type');
            $table->unsignedBigInteger('wishlistable_id');
            $table->decimal('target_price', 12, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['collection_id', 'wishlistable_type', 'wishlistable_id'], 'wishlist_items_unique');
            $table->index(['wishlistable_type', 'wishlistable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};