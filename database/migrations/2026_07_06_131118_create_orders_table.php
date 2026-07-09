<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('umkm_id')->constrained()->onDelete('cascade');
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending', 'paid', 'preparing', 'ready', 'picked_up', 'cancelled'])->default('pending');
            $table->string('qr_code')->nullable();
            $table->text('notes')->nullable();
            
            // Sistem Pembayaran Coin
            $table->enum('payment_method', ['coin', 'cash_on_pickup'])->default('cash_on_pickup');
            $table->decimal('coin_amount', 16, 4)->default(0);
            $table->decimal('coin_to_rupiah_rate', 15, 2)->nullable();
            $table->decimal('rupiah_equivalent', 15, 2)->nullable();
            
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['umkm_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
