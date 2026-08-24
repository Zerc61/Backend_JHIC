<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('voucher_id')->nullable()->after('total_price')->constrained()->nullOnDelete();
            $table->decimal('discount', 15, 2)->default(0)->after('voucher_id');
            $table->decimal('total_amount', 15, 2)->nullable()->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Voucher::class);
            $table->dropColumn(['voucher_id', 'discount', 'total_amount']);
        });
    }
};
