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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            
            // Reference ke transaksi yang di-refund
            $table->morphs('refundable'); // refundable_type, refundable_id
            
            // Detail refund
            $table->string('reason'); // order_cancelled, booking_cancelled, duplicate_payment, customer_request
            $table->text('description');
            $table->decimal('refund_amount', 15, 2);
            $table->string('refund_method'); // coin_wallet, original_payment, bank_transfer
            
            // Status
            $table->string('status')->default('pending'); // pending, approved, processing, completed, rejected
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();
            
            // Tracking
            $table->string('transaction_reference')->nullable(); // untuk bank transfer atau payment gateway
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('refund_number');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
