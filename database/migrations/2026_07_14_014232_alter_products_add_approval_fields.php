<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ubah enum status: tambah pending, approved, rejected (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE products MODIFY COLUMN status 
                ENUM('pending','approved','rejected','available','unavailable') 
                NOT NULL DEFAULT 'pending'
            ");
        }

        // 2. Tambah kolom admin_note untuk alasan approve/reject
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        // Hapus admin_note dulu
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'admin_note')) {
                $table->dropColumn('admin_note');
            }
        });

        // Kembalikan enum ke semula (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE products MODIFY COLUMN status 
                ENUM('available','unavailable') 
                NOT NULL DEFAULT 'available'
            ");
        }
    }
};