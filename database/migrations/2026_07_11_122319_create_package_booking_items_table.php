<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_booking_id')->constrained()->onDelete('cascade');
            $table->enum('item_type', ['hotel', 'destination_ticket', 'transportation', 'meal', 'benefit', 'other']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('qr_code')->nullable();
            $table->json('qr_data')->nullable()->comment('Payload yang di-encode ke QR');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_booking_items');
    }
};
