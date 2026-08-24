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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Reference ke transaksi
            $table->morphs('invoiceable'); // invoice_type, invoice_id
            
            // Detail transaksi
            $table->string('transaction_type'); // order, booking, top_up
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            
            // Pembayaran
            $table->string('payment_method'); // coin, cash_on_pickup, card
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->timestamp('paid_at')->nullable();
            
            // Detail tambahan
            $table->text('notes')->nullable();
            $table->text('items_json'); // JSON: item details
            $table->string('status')->default('active'); // active, cancelled, archived
            
            // Audit
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('invoice_number');
            $table->index('user_id');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
