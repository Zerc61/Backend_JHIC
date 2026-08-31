<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Base data (categories, facilities) — insertOrIgnore supaya idempotent
        $this->seedBase();

        // Core seeders in dependency order
        $this->call([
            UserSeeder::class,
            DestinationSeeder::class,
            HotelSeeder::class,
            TravelPackageSeeder::class,
            UmkmProductSeeder::class,
            EventSeeder::class,
            TransportTicketSeeder::class,
            ReviewSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('════════════════════════════════════════');
        $this->command->info('  Semua data test berhasil dibuat!');
        $this->command->info('════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('Akun test (password: password123):');
        $this->command->info('  Admin    : admin@ejt.com');
        $this->command->info('  Manager  : manager@ejt.com');
        $this->command->info('  UMKM     : umkm@ejt.com');
        $this->command->info('  Tourist  : tourist@ejt.com');
        $this->command->info('  Tourist  : andi@gmail.com');
        $this->command->info('  ... (12 tourists total)');
    }

    private function seedBase(): void
    {
        $now = now();

        // Destination Categories
        $destCats = [
            ['name' => 'Pantai',         'slug' => 'pantai',         'icon' => '🏖️'],
            ['name' => 'Pegunungan',     'slug' => 'pegunungan',     'icon' => '🏔️'],
            ['name' => 'Budaya',         'slug' => 'budaya',         'icon' => '🏛️'],
            ['name' => 'Air Terjun',     'slug' => 'air-terjun',     'icon' => '💧'],
            ['name' => 'Taman Nasional', 'slug' => 'taman-nasional', 'icon' => '🌿'],
            ['name' => 'Kuliner',        'slug' => 'kuliner',        'icon' => '🍜'],
            ['name' => 'Religi',         'slug' => 'religi',         'icon' => '🕌'],
            ['name' => 'Alam',           'slug' => 'alam',           'icon' => '🌳'],
        ];
        foreach ($destCats as $c) {
            DB::table('destination_categories')->insertOrIgnore(array_merge($c, [
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }

        // UMKM Categories
        $umkmCats = [
            ['name' => 'Kuliner',     'slug' => 'kuliner',     'icon' => '🍜'],
            ['name' => 'Oleh-oleh',   'slug' => 'oleh-oleh',   'icon' => '🎁'],
            ['name' => 'Kerajinan',   'slug' => 'kerajinan',   'icon' => '🧶'],
            ['name' => 'Fashion',     'slug' => 'fashion',     'icon' => '👗'],
            ['name' => 'Kecantikan',  'slug' => 'kecantikan',  'icon' => '💄'],
            ['name' => 'Pertanian',   'slug' => 'pertanian',   'icon' => '🌾'],
        ];
        foreach ($umkmCats as $c) {
            DB::table('umkm_categories')->insertOrIgnore(array_merge($c, [
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }

        // Facilities
        $facilities = [
            ['name' => 'Toilet',         'icon' => '🚻'],
            ['name' => 'Parkir',         'icon' => '🅿️'],
            ['name' => 'Mushola',        'icon' => '🕌'],
            ['name' => 'WiFi',           'icon' => '📶'],
            ['name' => 'Warung Makan',   'icon' => '🍽️'],
            ['name' => 'Restoran',       'icon' => '🍽️'],
            ['name' => 'Spot Foto',      'icon' => '📸'],
            ['name' => 'Gazebo',         'icon' => '🏠'],
            ['name' => 'Penginapan',     'icon' => '🏨'],
            ['name' => 'Rental Alat',    'icon' => '🤿'],
            ['name' => 'Toko Oleh-oleh', 'icon' => '🛍️'],
        ];
        foreach ($facilities as $f) {
            DB::table('facilities')->insertOrIgnore(array_merge($f, [
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }

        $this->command->info('📋 Base data (categories, facilities) siap');
    }
}
