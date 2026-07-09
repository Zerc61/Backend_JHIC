<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['name' => 'Toilet', 'icon' => '🚻'],
            ['name' => 'Parkir', 'icon' => '🅿️'],
            ['name' => 'Mushola', 'icon' => '🕌'],
            ['name' => 'WiFi', 'icon' => '📶'],
            ['name' => 'Toko Oleh-oleh', 'icon' => '🛍️'],
            ['name' => 'Restoran', 'icon' => '🍽️'],
            ['name' => 'Gazebo', 'icon' => '⛺'],
            ['name' => 'Spot Foto', 'icon' => '📸'],
        ];

        DB::table('facilities')->insert($facilities);
    }
}