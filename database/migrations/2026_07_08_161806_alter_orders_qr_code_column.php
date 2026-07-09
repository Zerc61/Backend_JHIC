<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Mengubah dari VARCHAR(255) menjadi TEXT agar muat string SVG yang panjang
        DB::statement('ALTER TABLE orders MODIFY COLUMN qr_code TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders MODIFY COLUMN qr_code VARCHAR(255) NULL');
    }
};