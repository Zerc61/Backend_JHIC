<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('comment');
            $table->string('video_url')->nullable()->after('photos');
            $table->unsignedInteger('helpful_count')->default(0)->after('video_url');
            $table->text('response_text')->nullable()->after('rating');
            $table->timestamp('response_at')->nullable()->after('response_text');
            $table->foreignId('response_by')->nullable()->after('response_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['response_by']);
            $table->dropColumn(['photos', 'video_url', 'helpful_count', 'response_text', 'response_at', 'response_by']);
        });
    }
};