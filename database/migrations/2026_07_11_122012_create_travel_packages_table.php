<?php

// database/migrations/2026_07_11_122012_create_travel_packages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('destination_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('hotel_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('transportation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('thumbnail')->nullable();
            $table->unsignedSmallInteger('duration_days')->default(1);
            $table->unsignedSmallInteger('duration_nights')->default(0);
            $table->decimal('price_per_person', 12, 2);
            $table->unsignedSmallInteger('max_travelers')->default(10);
            $table->json('included_items')->nullable()->comment('[ "Transport HiAce", "Hotel 2 malam" ]');
            $table->json('excluded_items')->nullable()->comment('[ "Bahan bakar" ]');
            $table->json('meals_included')->nullable()->comment('{ "breakfast": 3, "lunch": 2 }');
            $table->json('benefits')->nullable(); // ← after() dihapus
            $table->text('terms_conditions')->nullable();
            $table->enum('status', ['published', 'draft', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_packages');
    }
};
