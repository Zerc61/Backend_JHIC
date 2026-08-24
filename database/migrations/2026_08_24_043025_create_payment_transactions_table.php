<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Reference ke transaksi
            $table->morphs('payable'); // payable_type, payable_id (Order, Booking)
            
            // Amount & details
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('IDR');
            $table->string('payment_method'); // card, bank_transfer, e_wallet, qris
            $table->text('description')->nullable();
            
            // Midtrans details
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('midtrans_transaction_id')->nullable()->unique();
            $table->json('midtrans_response')->nullable();
            
            // Status
            $table->string('status')->default('pending'); // pending, processing, success, failed, cancelled, expired
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Retry tracking
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('transaction_number');
            $table->index('user_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
