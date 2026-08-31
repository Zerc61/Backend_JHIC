<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('loyalty_tier')->default('bronze')->after('avatar');
            $table->timestamp('last_coin_activity_at')->nullable()->after('loyalty_tier');
            $table->string('referral_code')->nullable()->unique()->after('last_coin_activity_at');
            $table->foreignId('referrer_user_id')->nullable()->after('referral_code')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_user_id']);
            $table->dropColumn(['loyalty_tier', 'last_coin_activity_at', 'referral_code', 'referrer_user_id']);
        });
    }
};