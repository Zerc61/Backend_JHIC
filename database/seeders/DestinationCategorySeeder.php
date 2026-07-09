<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DestinationCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pantai', 'slug' => 'pantai', 'icon' => '🌊'],
            ['name' => 'Pegunungan', 'slug' => 'pegunungan', 'icon' => '⛰️'],
            ['name' => 'Budaya', 'slug' => 'budaya', 'icon' => '🏛️'],
            ['name' => 'Sejarah', 'slug' => 'sejarah', 'icon' => '📜'],
            ['name' => 'Kuliner', 'slug' => 'kuliner', 'icon' => '🍜'],
        ];

        DB::table('destination_categories')->insert($categories);
    }
}