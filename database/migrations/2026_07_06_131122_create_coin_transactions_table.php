<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 16, 4);
            $table->decimal('balance_before', 16, 4);
            $table->decimal('balance_after', 16, 4);
            $table->string('description');
            $table->string('reference_type')->nullable(); // TopUpTransaction, Order
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};