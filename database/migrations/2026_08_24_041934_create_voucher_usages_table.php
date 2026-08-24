<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Reference ke transaksi
            $table->morphs('usable'); // usable_type, usable_id (Order, Booking)
            
            // Discount applied
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('original_amount', 10, 2);
            $table->decimal('final_amount', 10, 2);
            
            // Status
            $table->string('status')->default('applied'); // applied, cancelled, refunded
            $table->timestamp('used_at');
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            
            $table->index('voucher_id');
            $table->index('user_id');
            $table->index('used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
    }
};
