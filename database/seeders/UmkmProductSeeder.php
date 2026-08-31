<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UmkmProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏪 Membuat 50+ UMKM + produk...');
        mt_srand(20260417);

        $now = now();
        $umkmUserId = DB::table('users')->where('role', 'umkm')->first()?->id ?? 1;
        $destRows = DB::table('destinations')
            ->whereNotIn('slug', ['pantai-kuta-lombok', 'pantai-pink-lombok', 'gunung-rinjani', 'desa-sade-lombok', 'pantai-tanjung-aan'])
            ->get(['id', 'name', 'slug']);

        if ($destRows->isEmpty()) {
            $this->command->warn('  ⚠️ Belum ada destinasi. Jalankan DestinationSeeder dulu.');
            return;
        }

        // Ensure UMKM categories exist
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
        $catIds = DB::table('umkm_categories')->pluck('id')->toArray();

        $foodImg = 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';
        $craftImg = 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&q=80';

        $umkmNames = [
            'Warung Bu Sari', 'Kedai Kopi Jawa', 'Toko Oleh-Oleh Mas', 'Bengkel Kerajinan Pak Darmi',
            'Rumah Makan Padang Bu Rina', 'Cafe Sunset Malang', 'Toko Batik Tulis Surabaya',
            'Home Industry Keripik Malang', 'Sentra Kerajinan Batu', 'Pusat Oleh-Oleh Banyuwangi',
            'Warung Bakso Malang Asli', 'Rumah Teh Kediri', 'Toko Souvenir Bromo',
            'Kedai Sate Madura', 'Pusat Batik Gedog Tuban', 'Koperasi Tani Kopi Ijen',
            'UMKM Tenun Ikat Banyuwangi', 'Rumah Produksi Sambal Ijo', 'Galeri Seni Batu',
            'Sentra Payung Hujan Malang', 'Toko Mainan Kayu Jepara', 'Workshop Perak Lawang',
            'Kedai Es Campur Malang', 'Rumah Roti Blitar', 'Toko Kain Tradisional',
            'Pusat Souvenir Malang', 'Warung Sate Madura', 'Cafe Teras Bunga Batu',
            'Home Industry Dodol Malang', 'Sentra Gitar Akustik Batu', 'Toko Sepatu Handmade Surabaya',
            'Bengkel Seni Patung', 'Rumah Cokelat Malang', 'Toko Parfum Natural',
            'Workshop Lukisan Malang', 'Sentra Embroidery Tulungagung', 'Kedai Jamu Tradisional',
            'Rumah Produksi Tahu Sumedang', 'Toko Aksesoris Unik', 'Galeri Keramik Batu',
            'Sentra anyaman bambu', 'Toko Madu Hutan Ijen', 'Home Industry Kerupuk Malang',
            'Rumah Teh Herbal', 'Workshop Tekstil Modern', 'Pusat Cenderamata Malang',
            'Toko Perhiasan handmade', 'Cafe Hidden Gems Batu', 'Warung Makan Sunda Bu Imas',
            'Toko Snack Sehat Malang', 'Sentra Alat Musik Tradisional', 'Rumah Kain Laos Malang',
            'Toko Parfum Arab Surabaya', 'Home Industry Mie Malang', 'Galeri Seni Rupa Batu',
        ];

        $products = [
            ['name' => 'Keripik Apel Malang',     'price' => 25000,  'unit' => 'pack'],
            ['name' => 'Sambal Terasi Buatan',     'price' => 18000,  'unit' => 'botol'],
            ['name' => 'Kopi Ijen 250gr',          'price' => 55000,  'unit' => 'pack'],
            ['name' => 'Kain Batik Tulis',         'price' => 350000, 'unit' => 'pcs'],
            ['name' => 'Gitar Akustik handmade',   'price' => 750000, 'unit' => 'pcs'],
            ['name' => 'Dodol Malang',             'price' => 30000,  'unit' => 'pack'],
            ['name' => 'Madu Hutan Asli',          'price' => 85000,  'unit' => 'botol'],
            ['name' => 'Tahu Sumedang',            'price' => 15000,  'unit' => 'pack'],
            ['name' => 'Cokelat Praline',          'price' => 65000,  'unit' => 'box'],
            ['name' => 'Sambal Ijo Premium',       'price' => 22000,  'unit' => 'botol'],
            ['name' => 'Kerupuk Udang',            'price' => 20000,  'unit' => 'pack'],
            ['name' => 'Batik Gedog Tuban',        'price' => 500000, 'unit' => 'pcs'],
            ['name' => 'Parfum Natural Rose',      'price' => 125000, 'unit' => 'botol'],
            ['name' => 'Mie Malang Instan',        'price' => 12000,  'unit' => 'pack'],
            ['name' => 'Teh Herbal Chamomile',     'price' => 35000,  'unit' => 'pack'],
            ['name' => 'Payung Lukis',             'price' => 85000,  'unit' => 'pcs'],
            ['name' => 'Perak Lawang Necklace',    'price' => 175000, 'unit' => 'pcs'],
            ['name' => 'Jamu Kunyit Asam',         'price' => 10000,  'unit' => 'botol'],
            ['name' => 'Snack Sehat Granola',      'price' => 40000,  'unit' => 'pack'],
            ['name' => 'Embroidery Wall Art',      'price' => 250000, 'unit' => 'pcs'],
            ['name' => 'Souvenir Magnet Malang',   'price' => 15000,  'unit' => 'pcs'],
            ['name' => 'Sate Madura Frozen',       'price' => 45000,  'unit' => 'pack'],
            ['name' => 'Dodol Wijen',              'price' => 28000,  'unit' => 'pack'],
            ['name' => 'Keripik Singkong Balado',  'price' => 15000,  'unit' => 'pack'],
            ['name' => 'Minyak Zaitun Lokal',      'price' => 60000,  'unit' => 'botol'],
            ['name' => 'Kaos Batik Malang',        'price' => 95000,  'unit' => 'pcs'],
            ['name' => 'Sepatu Kulit Handmade',    'price' => 450000, 'unit' => 'pcs'],
            ['name' => 'Patung Kayu Jati',         'price' => 350000, 'unit' => 'pcs'],
            ['name' => 'Lukisan Abstrak',          'price' => 800000, 'unit' => 'pcs'],
            ['name' => 'Roti Manis Malang',        'price' => 18000,  'unit' => 'pack'],
            ['name' => 'Kain Laos Premium',        'price' => 150000, 'unit' => 'meter'],
            ['name' => 'Mainan Edukasi Kayu',      'price' => 125000, 'unit' => 'pcs'],
            ['name' => 'Mie Ayam Malang Frozen',   'price' => 30000,  'unit' => 'pack'],
            ['name' => 'Keramik Hias Batu',        'price' => 75000,  'unit' => 'pcs'],
            ['name' => 'Madu Klengeng',            'price' => 95000,  'unit' => 'botol'],
        ];

        $destIds = $destRows->pluck('id')->all();
        $destById = $destRows->keyBy('id');

        // Wilayah keyword → destinasi slug untuk koherensi nama UMKM
        $regionKeywords = [
            'malang'     => 'pantai-balekambang',
            'batu'       => 'taman-selecta',
            'surabaya'   => 'tugu-pahlawan',
            'banyuwangi' => 'gunung-ijen',
            'kediri'     => 'candi-penataran',
            'blitar'     => 'makam-bung-karno',
            'tuban'      => 'bengawan-solo',
            'madura'     => 'makam-sunan-giri',
            'sidoarjo'   => 'lumpur-lapindo',
            'pacitan'    => 'pantai-klayar',
            'bromo'      => 'gunung-bromo',
            'lawang'     => 'coban-rondo',
        ];
        $destSlugById = [];
        foreach ($destRows as $d) $destSlugById[$d->id] = $d->slug;

        $created = 0;

        for ($i = 0; $i < 55; $i++) {
            $name = $umkmNames[$i % count($umkmNames)];
            $slug = Str::slug($name) . '-' . ($i + 1);

            if (DB::table('umkms')->where('slug', $slug)->exists()) continue;

            $catId = $catIds[$i % count($catIds)];

            // Cari destinasi wilayah dari nama, fallback giliran index
            $destId = null;
            $lower = strtolower($name);
            foreach ($regionKeywords as $kw => $dSlug) {
                if (Str::contains($lower, $kw)) {
                    $found = $destRows->firstWhere('slug', $dSlug);
                    if ($found) { $destId = $found->id; break; }
                }
            }
            $destId = $destId ?? $destIds[$i % count($destIds)];

            $uid = DB::table('umkms')->insertGetId([
                'user_id'          => $umkmUserId,
                'destination_id'   => $destId,
                'umkm_category_id' => $catId,
                'name'             => $name,
                'slug'             => $slug,
                'description'      => "{$name} menyediakan produk-produk berkualitas dari UMKM lokal Jawa Timur. Dibuat dengan penuh cinta dan keahlian tradisional.",
                'address'          => 'Jl. Wisata No. ' . rand(1, 100) . ', Jawa Timur',
                'latitude'         => -7.5 + rand(-100, 100) / 100,
                'longitude'        => 112.0 + rand(-100, 100) / 100,
                'phone'            => '0812' . rand(10000000, 99999999),
                'opening_hours'    => rand(0, 1) ? '08:00 - 21:00' : '09:00 - 20:00',
                'photo'            => $craftImg,
                'status'           => 'active',
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            // 2-3 produk per UMKM (deterministik)
            $offset = $i * 3;
            $picked = collect($products)->slice($offset % count($products), 2 + ($i % 2));
            foreach ($picked as $p) {
                $pid = DB::table('products')->insertGetId([
                    'umkm_id'     => $uid,
                    'name'        => $p['name'],
                    'slug'        => Str::slug($p['name']) . '-' . $uid,
                    'description' => "{$p['name']} dari UMKM {$name}. Kualitas terjamin, harga bersahabat.",
                    'price'       => $p['price'],
                    'stock'       => rand(10, 100),
                    'unit'        => $p['unit'],
                    'image'       => $foodImg,
                    'status'      => 'available',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);

                DB::table('product_images')->insert([
                    'product_id' => $pid,
                    'image'      => $foodImg,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $created++;
        }

        $this->command->info("  ✅ {$created} UMKM + produk baru");
    }
}
