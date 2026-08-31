<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('description');
            $table->boolean('is_expired')->default(false)->after('expires_at');
        });

        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->enum('type', ['credit', 'debit', 'earn', 'redeem', 'expire', 'adjust'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->enum('type', ['credit', 'debit'])->change();
        });

        Schema::table('coin_transactions', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'is_expired']);
        });
    }
};