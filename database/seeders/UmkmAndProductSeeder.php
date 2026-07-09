<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Enums\UserStatus;

class UmkmAndProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. BUAT DULU 1 USER DUMMY UMKM SEBAGAI PEMILIK
        $user = DB::table('users')->where('email', 'umkm@nusatrip.com')->first();
        if (!$user) {
            $userId = DB::table('users')->insertGetId([
                'name' => 'UMKM Demo NusaTrip',
                'email' => 'umkm@nusatrip.com',
                'password' => Hash::make('password123'),
                'phone' => '081234567890',
                'role' => 'umkm',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Buat wallet untuk user ini
            DB::table('wallets')->insert([
                'user_id' => $userId,
                'balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $userId = $user->id;
        }

        // 2. AMBIL DATA DESTINASI DAN KATEGORI
        $destinations = DB::table('destinations')->pluck('id');
        $categories = DB::table('umkm_categories')->pluck('id');

        $umkms = [
            ['name' => 'Warung Seafood Pak Budi', 'slug' => 'warung-seafood-pak-budi', 'description' => 'Seafood segar langsung dari nelayan pantai. Ikan bakar dan cumi goreng menjadi andalan.', 'phone' => '0812340001', 'opening_hours' => '10:00 - 22:00', 'category_id' => 1],
            ['name' => 'Kedai Kopi Lombok', 'slug' => 'kedai-kopi-lombok', 'description' => 'Kopi robusta khas Lombok yang dipanggang tradisional. Suasana santai dengan pemandangan sawah.', 'phone' => '0812340002', 'opening_hours' => '08:00 - 21:00', 'category_id' => 1],
            ['name' => 'Toko Oleh-Oleh Mutiara', 'slug' => 'toko-oleh-oleh-mutiara', 'description' => 'Menyediakan mutiara asli Lombok, manik-manik, dan suvenir khas Sasak.', 'phone' => '0812340003', 'opening_hours' => '09:00 - 20:00', 'category_id' => 2],
            ['name' => 'Tenun Sukarare', 'slug' => 'tenun-sukarare', 'description' => 'Kain tenun tradisional khas Suku Sasak yang ditenun manual dengan motif unik.', 'phone' => '0812340004', 'opening_hours' => '08:00 - 17:00', 'category_id' => 3],
            ['name' => 'Ayam Taliwang Bu Ani', 'slug' => 'ayam-taliwang-bu-ani', 'description' => 'Ayam taliwang legendaris dengan bumbu khas yang pedas dan nasi hangat.', 'phone' => '0812340005', 'opening_hours' => '11:00 - 21:00', 'category_id' => 1],
        ];

        foreach ($umkms as $umkm) {
            $destId = $destinations->random();
            DB::table('umkms')->insert([
                'user_id' => $userId, // <-- MENGGUNAKAN ID USER DUMMY YANG BARU DIBUAT
                'destination_id' => $destId,
                'umkm_category_id' => $umkm['category_id'],
                'name' => $umkm['name'],
                'slug' => $umkm['slug'],
                'description' => $umkm['description'],
                'address' => 'Sekitar Destinasi ID ' . $destId,
                'latitude' => -8.8000 + rand(-100, 100) / 1000,
                'longitude' => 116.3000 + rand(-100, 100) / 1000,
                'phone' => $umkm['phone'],
                'opening_hours' => $umkm['opening_hours'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. TAMBAHKAN PRODUK
        $umkmIds = DB::table('umkms')->pluck('id');
        $productsData = [
            ['name' => 'Ikan Bakar Segar', 'price' => 50000, 'unit' => 'porsi'],
            ['name' => 'Cumi Goreng Tepung', 'price' => 35000, 'unit' => 'porsi'],
            ['name' => 'Kopi Lombok 200gr', 'price' => 45000, 'unit' => 'pack'],
            ['name' => 'Kalung Mutiara Air Tawar', 'price' => 150000, 'unit' => 'pcs'],
            ['name' => 'Kain Tenun 2 Meter', 'price' => 250000, 'unit' => 'meter'],
            ['name' => 'Ayam Taliwang', 'price' => 40000, 'unit' => 'porsi'],
            ['name' => 'Plecing Kangkung', 'price' => 15000, 'unit' => 'porsi'],
            ['name' => 'Gelang Manik-Manik', 'price' => 25000, 'unit' => 'pcs'],
        ];

        foreach ($umkmIds as $umkmId) {
            $randomProducts = collect($productsData)->random(rand(2, 3));
            foreach ($randomProducts as $prod) {
                DB::table('products')->insert([
                    'umkm_id' => $umkmId,
                    'name' => $prod['name'],
                    'slug' => \Illuminate\Support\Str::slug($prod['name']) . '-' . $umkmId,
                    'description' => 'Produk berkualitas terbaik dari UMKM lokal.',
                    'price' => $prod['price'],
                    'stock' => rand(10, 50),
                    'unit' => $prod['unit'],
                    'image' => null,
                    'status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}