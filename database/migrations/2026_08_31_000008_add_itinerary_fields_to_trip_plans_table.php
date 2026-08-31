<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('title');
            $table->date('end_date')->nullable()->after('start_date');
            $table->boolean('is_public')->default(false)->after('estimated_cost');
            $table->string('share_token', 64)->nullable()->unique()->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('trip_plans', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn(['start_date', 'end_date', 'is_public', 'share_token']);
        });
    }
};