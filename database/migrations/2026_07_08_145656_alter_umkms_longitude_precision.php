<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE umkms MODIFY COLUMN longitude DECIMAL(11, 8) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE umkms MODIFY COLUMN longitude DECIMAL(10, 8) NULL');
    }
};