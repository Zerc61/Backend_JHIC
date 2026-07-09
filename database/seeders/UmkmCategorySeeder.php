<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UmkmCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Kuliner', 'slug' => 'kuliner', 'icon' => '🍜'],
            ['name' => 'Oleh-oleh', 'slug' => 'oleh-oleh', 'icon' => '🎁'],
            ['name' => 'Kerajinan', 'slug' => 'kerajinan', 'icon' => '🧶'],
        ];

        // insertOrIgnore akan mengabaikan data jika slug-nya sudah ada
        DB::table('umkm_categories')->insertOrIgnore($categories);
    }
}