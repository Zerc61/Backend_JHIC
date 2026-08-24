<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE umkms MODIFY COLUMN longitude DECIMAL(11, 8) NULL');
        } else {
            // SQLite tidak mendukung MODIFY, skip untuk SQLite
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE umkms MODIFY COLUMN longitude DECIMAL(10, 8) NULL');
        }
    }
};