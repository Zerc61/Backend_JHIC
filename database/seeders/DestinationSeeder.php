<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏖️ Membuat 50+ destinasi Jawa Timur...');

        $now = now();

        $managerId = DB::table('users')->where('role', 'manager')->first()?->id ?? 1;

        // Ensure categories exist
        $cats = [
            ['name' => 'Pantai',         'slug' => 'pantai',         'icon' => '🏖️'],
            ['name' => 'Pegunungan',     'slug' => 'pegunungan',     'icon' => '🏔️'],
            ['name' => 'Budaya',         'slug' => 'budaya',         'icon' => '🏛️'],
            ['name' => 'Air Terjun',     'slug' => 'air-terjun',     'icon' => '💧'],
            ['name' => 'Taman Nasional', 'slug' => 'taman-nasional', 'icon' => '🌿'],
            ['name' => 'Kuliner',        'slug' => 'kuliner',        'icon' => '🍜'],
            ['name' => 'Religi',         'slug' => 'religi',         'icon' => '🕌'],
            ['name' => 'Alam',           'slug' => 'alam',           'icon' => '🌳'],
        ];
        foreach ($cats as $c) {
            DB::table('destination_categories')->insertOrIgnore(array_merge($c, [
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }

        $catMap = [];
        foreach ($cats as $c) {
            $catMap[$c['slug']] = DB::table('destination_categories')->where('slug', $c['slug'])->first()->id;
        }

        $img = [
            'beach'  => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&q=80',
            'mt'     => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=800&q=80',
            'temple' => 'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=800&q=80',
            'water'  => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80',
            'forest' => 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=800&q=80',
            'city'   => 'https://images.unsplash.com/photo-1519046904884-53103b34b206?w=800&q=80',
            'food'   => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80',
            'rice'   => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800&q=80',
            'sun'    => 'https://images.unsplash.com/photo-1509233725247-49e657c54213?w=800&q=80',
            'lake'   => 'https://images.unsplash.com/photo-1439066615861-d1af74d74000?w=800&q=80',
            'travel' => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=800&q=80',
            'sunset' => 'https://images.unsplash.com/photo-1495616811223-4d98c6e9c869?w=800&q=80',
        ];

        $destinations = [
            // Malang
            ['cat' => 'pantai',   'name' => 'Pantai Sendang Biru',    'slug' => 'pantai-sendang-biru',    'lat' => -8.4453, 'lng' => 114.2581, 'price' => 10000,  'est' => 150000,  'img' => 'beach'],
            ['cat' => 'pantai',   'name' => 'Pantai Bajul Mati',      'slug' => 'pantai-bajul-mati',      'lat' => -8.4106, 'lng' => 114.2267, 'price' => 10000,  'est' => 120000,  'img' => 'beach'],
            ['cat' => 'pantai',   'name' => 'Pantai Goa Cina',        'slug' => 'pantai-goa-cina',        'lat' => -8.3956, 'lng' => 114.2419, 'price' => 5000,   'est' => 100000,  'img' => 'beach'],
            ['cat' => 'pantai',   'name' => 'Pantai Balekambang',     'slug' => 'pantai-balekambang',     'lat' => -8.3653, 'lng' => 114.2175, 'price' => 10000,  'est' => 100000,  'img' => 'beach'],
            ['cat' => 'pantai',   'name' => 'Pantai Ngliyep',         'slug' => 'pantai-ngliyep',         'lat' => -8.3844, 'lng' => 114.1864, 'price' => 10000,  'est' => 100000,  'img' => 'beach'],
            ['cat' => 'air-terjun','name' => 'Coban Rondo',           'slug' => 'coban-rondo',            'lat' => -7.9167, 'lng' => 112.4833, 'price' => 20000,  'est' => 80000,   'img' => 'water'],
            ['cat' => 'air-terjun','name' => 'Coban Talun',           'slug' => 'coban-talun',            'lat' => -7.8500, 'lng' => 112.5167, 'price' => 15000,  'est' => 75000,   'img' => 'water'],
            ['cat' => 'air-terjun','name' => 'Coban Rais',            'slug' => 'coban-rais',             'lat' => -7.9333, 'lng' => 112.5500, 'price' => 15000,  'est' => 80000,   'img' => 'water'],
            ['cat' => 'alam',     'name' => 'Taman Selecta',          'slug' => 'taman-selecta',          'lat' => -7.9167, 'lng' => 112.5500, 'price' => 40000,  'est' => 150000,  'img' => 'forest'],
            ['cat' => 'alam',     'name' => 'Jatim Park 2',           'slug' => 'jatim-park-2',           'lat' => -7.8833, 'lng' => 112.5333, 'price' => 100000, 'est' => 200000,  'img' => 'travel'],
            ['cat' => 'alam',     'name' => 'Batu Night Spectacular', 'slug' => 'batu-night-spectacular', 'lat' => -7.8833, 'lng' => 112.5333, 'price' => 100000, 'est' => 200000,  'img' => 'city'],
            ['cat' => 'kuliner',  'name' => 'Jalan Alun-Alun Batu',   'slug' => 'jalan-alun-alun-batu',   'lat' => -7.8764, 'lng' => 112.5241, 'price' => 0,      'est' => 100000,  'img' => 'food'],

            // Bromo & Tengger
            ['cat' => 'pegunungan','name' => 'Gunung Bromo',          'slug' => 'gunung-bromo',           'lat' => -7.9425, 'lng' => 112.9530, 'price' => 220000, 'est' => 500000,  'img' => 'mt'],
            ['cat' => 'pegunungan','name' => 'Bukit Penanjakan',      'slug' => 'bukit-penanjakan',       'lat' => -7.9219, 'lng' => 112.9422, 'price' => 220000, 'est' => 350000,  'img' => 'mt'],
            ['cat' => 'pegunungan','name' => 'Savanna Bromo',         'slug' => 'savanna-bromo',          'lat' => -7.9200, 'lng' => 112.9700, 'price' => 220000, 'est' => 400000,  'img' => 'rice'],
            ['cat' => 'alam',     'name' => 'Lautan Pasir Bromo',     'slug' => 'lautan-pasir-bromo',     'lat' => -7.9400, 'lng' => 112.9600, 'price' => 220000, 'est' => 350000,  'img' => 'sun'],

            // Surabaya
            ['cat' => 'budaya',   'name' => 'Tugu Pahlawan',          'slug' => 'tugu-pahlawan',          'lat' => -7.2452, 'lng' => 112.7360, 'price' => 0,      'est' => 50000,   'img' => 'city'],
            ['cat' => 'budaya',   'name' => 'Cagar Budaya Kayun',     'slug' => 'cagar-budaya-kayun',     'lat' => -7.2892, 'lng' => 112.7352, 'price' => 0,      'est' => 30000,   'img' => 'temple'],
            ['cat' => 'kuliner',  'name' => 'Pasar Atom',             'slug' => 'pasar-atom',             'lat' => -7.2454, 'lng' => 112.7380, 'price' => 0,      'est' => 200000,  'img' => 'city'],
            ['cat' => 'kuliner',  'name' => 'Kenjeran Park',          'slug' => 'kenjeran-park',          'lat' => -7.2254, 'lng' => 112.7530, 'price' => 10000,  'est' => 150000,  'img' => 'beach'],
            ['cat' => 'alam',     'name' => 'Surabaya Zoo',           'slug' => 'surabaya-zoo',           'lat' => -7.2987, 'lng' => 112.7260, 'price' => 50000,  'est' => 150000,  'img' => 'forest'],

            // Banyuwangi
            ['cat' => 'pantai',   'name' => 'Pantai Merah (Red Island)','slug' => 'pantai-merah',         'lat' => -8.3500, 'lng' => 114.0833, 'price' => 10000,  'est' => 150000,  'img' => 'sunset'],
            ['cat' => 'pantai',   'name' => 'Pantai Plengkung',       'slug' => 'pantai-plengkung',       'lat' => -8.3167, 'lng' => 114.3333, 'price' => 15000,  'est' => 200000,  'img' => 'beach'],
            ['cat' => 'pantai',   'name' => 'Pantai Sukamade',        'slug' => 'pantai-sukamade',        'lat' => -8.2167, 'lng' => 114.0833, 'price' => 25000,  'est' => 300000,  'img' => 'beach'],
            ['cat' => 'taman-nasional','name' => 'Taman Nasional Baluran','slug' => 'taman-nasional-baluran','lat' => -7.8500, 'lng' => 114.3500, 'price' => 200000, 'est' => 500000,  'img' => 'rice'],
            ['cat' => 'pegunungan','name' => 'Gunung Ijen',           'slug' => 'gunung-ijen',            'lat' => -8.0583, 'lng' => 114.2417, 'price' => 100000, 'est' => 350000,  'img' => 'mt'],
            ['cat' => 'pegunungan','name' => 'Kawah Ijen Blue Fire',  'slug' => 'kawah-ijen-blue-fire',   'lat' => -8.0583, 'lng' => 114.2417, 'price' => 100000, 'est' => 500000,  'img' => 'mt'],

            // Kediri
            ['cat' => 'budaya',   'name' => 'Candi Penataran',        'slug' => 'candi-penataran',        'lat' => -8.0167, 'lng' => 112.1000, 'price' => 15000,  'est' => 75000,   'img' => 'temple'],
            ['cat' => 'alam',     'name' => 'Kediri Tea Plantation',  'slug' => 'kediri-tea-plantation',  'lat' => -7.8500, 'lng' => 112.0000, 'price' => 20000,  'est' => 100000,  'img' => 'rice'],
            ['cat' => 'kuliner',  'name' => 'Tahu Porong Kediri',     'slug' => 'tahu-porong-kediri',     'lat' => -7.9667, 'lng' => 112.0167, 'price' => 0,      'est' => 50000,   'img' => 'food'],

            // Blitar
            ['cat' => 'budaya',   'name' => 'Makam Bung Karno',       'slug' => 'makam-bung-karno',       'lat' => -8.0983, 'lng' => 112.1681, 'price' => 0,      'est' => 50000,   'img' => 'city'],
            ['cat' => 'pantai',   'name' => 'Pantai Serang',          'slug' => 'pantai-serang-blitar',   'lat' => -8.2167, 'lng' => 112.3333, 'price' => 5000,   'est' => 80000,   'img' => 'beach'],
            ['cat' => 'pegunungan','name' => 'Gunung Kelud',          'slug' => 'gunung-kelud',           'lat' => -7.9333, 'lng' => 112.3083, 'price' => 50000,  'est' => 200000,  'img' => 'mt'],

            // Tulungagung
            ['cat' => 'pantai',   'name' => 'Pantai Popoh',           'slug' => 'pantai-popoh',           'lat' => -8.2333, 'lng' => 111.8667, 'price' => 5000,   'est' => 80000,   'img' => 'beach'],
            ['cat' => 'pantai',   'name' => 'Pantai Sidem',           'slug' => 'pantai-sidem',           'lat' => -8.2167, 'lng' => 111.8500, 'price' => 5000,   'est' => 75000,   'img' => 'beach'],
            ['cat' => 'alam',     'name' => 'Gua Maharani',           'slug' => 'gua-maharani',           'lat' => -8.2500, 'lng' => 111.8833, 'price' => 15000,  'est' => 80000,   'img' => 'forest'],

            // Trenggalek
            ['cat' => 'pantai',   'name' => 'Pantai Watu Lanyar',     'slug' => 'pantai-watu-lanyar',     'lat' => -8.0833, 'lng' => 111.6667, 'price' => 5000,   'est' => 75000,   'img' => 'beach'],
            ['cat' => 'air-terjun','name' => 'Curug Grobyok',         'slug' => 'curug-grobyok',          'lat' => -8.1333, 'lng' => 111.7000, 'price' => 10000,  'est' => 80000,   'img' => 'water'],

            // Pacitan
            ['cat' => 'pantai',   'name' => 'Pantai Klayar',          'slug' => 'pantai-klayar',          'lat' => -8.1667, 'lng' => 110.9833, 'price' => 10000,  'est' => 100000,  'img' => 'beach'],
            ['cat' => 'pantai',   'name' => 'Pantai Watu Karung',     'slug' => 'pantai-watu-karung',     'lat' => -8.1833, 'lng' => 110.9500, 'price' => 5000,   'est' => 80000,   'img' => 'beach'],
            ['cat' => 'pegunungan','name' => 'Gua Gong',              'slug' => 'gua-gong',               'lat' => -8.1500, 'lng' => 111.0167, 'price' => 20000,  'est' => 80000,   'img' => 'forest'],

            // Madiun
            ['cat' => 'budaya',   'name' => 'Alun-Alun Madiun',       'slug' => 'alun-alun-madiun',       'lat' => -7.6300, 'lng' => 111.5230, 'price' => 0,      'est' => 50000,   'img' => 'city'],
            ['cat' => 'kuliner',  'name' => 'Bakso Madiun',           'slug' => 'bakso-madiun',           'lat' => -7.6300, 'lng' => 111.5230, 'price' => 0,      'est' => 50000,   'img' => 'food'],

            // Nganjuk
            ['cat' => 'air-terjun','name' => 'Air Terjun Selo Pirang','slug' => 'air-terjun-selo-pirang', 'lat' => -7.7333, 'lng' => 111.8500, 'price' => 10000,  'est' => 80000,   'img' => 'water'],

            // Jombang
            ['cat' => 'religi',   'name' => 'Taman Mini Jatim Park',  'slug' => 'taman-mini-jatim-park',  'lat' => -7.5500, 'lng' => 112.2333, 'price' => 50000,  'est' => 150000,  'img' => 'temple'],

            // Mojokerto
            ['cat' => 'budaya',   'name' => 'Candi Jabung',           'slug' => 'candi-jabung',           'lat' => -7.5000, 'lng' => 112.4167, 'price' => 10000,  'est' => 75000,   'img' => 'temple'],
            ['cat' => 'budaya',   'name' => 'Candi Tikus',            'slug' => 'candi-tikus',            'lat' => -7.5167, 'lng' => 112.4333, 'price' => 10000,  'est' => 75000,   'img' => 'temple'],

            // Sidoarjo
            ['cat' => 'alam',     'name' => ' Lumpur Lapindo',         'slug' => 'lumpur-lapindo',         'lat' => -7.4333, 'lng' => 112.6500, 'price' => 25000,  'est' => 100000,  'img' => 'sun'],

            // Gresik
            ['cat' => 'religi',   'name' => 'Makam Sunan Giri',       'slug' => 'makam-sunan-giri',       'lat' => -7.1333, 'lng' => 112.6333, 'price' => 5000,   'est' => 75000,   'img' => 'temple'],

            // Lamongan
            ['cat' => 'kuliner',  'name' => 'Soto Lamongan',          'slug' => 'soto-lamongan',          'lat' => -7.1167, 'lng' => 112.3167, 'price' => 0,      'est' => 50000,   'img' => 'food'],

            // Bojonegoro
            ['cat' => 'alam',     'name' => 'Bengawan Solo',           'slug' => 'bengawan-solo',          'lat' => -7.1500, 'lng' => 111.8833, 'price' => 0,      'est' => 50000,   'img' => 'water'],
        ];

        $created = 0;

        foreach ($destinations as $d) {
            if (DB::table('destinations')->where('slug', $d['slug'])->exists()) continue;

            $did = DB::table('destinations')->insertGetId([
                'destination_category_id' => $catMap[$d['cat']] ?? 1,
                'manager_id'             => $managerId,
                'name'                   => $d['name'],
                'slug'                   => $d['slug'],
                'description'            => "{$d['name']} adalah destinasi wisata terbaik di Jawa Timur yang wajib dikunjungi. Nikmati pengalaman tak terlupakan bersama keluarga dan teman.",
                'address'                => 'Jawa Timur, Indonesia',
                'latitude'               => $d['lat'],
                'longitude'              => $d['lng'],
                'open_hour'              => '07:00',
                'close_hour'             => '17:00',
                'ticket_price'           => $d['price'],
                'estimated_cost'         => $d['est'],
                'phone'                  => '0812' . rand(10000000, 99999999),
                'status'                 => 'published',
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            // 2 galleries per destination
            foreach ([0, 1] as $i) {
                DB::table('destination_galleries')->insert([
                    'destination_id' => $did,
                    'image'          => $img[$d['img']] ?? $img['beach'],
                    'caption'        => "{$d['name']} — Foto " . ($i + 1),
                    'sort_order'     => $i,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }

            // Random facilities
            $facIds = DB::table('facilities')->pluck('id');
            if ($facIds->isNotEmpty()) {
                foreach ($facIds->random(rand(2, 4)) as $facId) {
                    DB::table('destination_facility')->insertOrIgnore([
                        'destination_id' => $did,
                        'facility_id'    => $facId,
                    ]);
                }
            }

            $created++;
        }

        $this->command->info("  ✅ {$created} destinasi baru");
    }
}
