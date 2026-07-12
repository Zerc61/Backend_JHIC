<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Membuat semua data test...');

        // ============================================================
        // UNSPLASH IMAGE POOLS
        // ============================================================
        $img = [
            // Pantai
            'beach_1'  => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&q=80',
            'beach_2'  => 'https://images.unsplash.com/photo-1519046904884-53103b34b206?w=800&q=80',
            'beach_3'  => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80',
            'beach_4'  => 'https://images.unsplash.com/photo-1509233725247-49e657c54213?w=800&q=80',
            'beach_5'  => 'https://images.unsplash.com/photo-1439066615861-d1af74d74000?w=800&q=80',
            'beach_6'  => 'https://images.unsplash.com/photo-1510414842594-a61c69b5ae57?w=800&q=80',
            'beach_7'  => 'https://images.unsplash.com/photo-1468413253725-0d5181091126?w=800&q=80',
            'beach_8'  => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&q=80',

            // Gunung
            'mt_1'    => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=800&q=80',
            'mt_2'    => 'https://images.unsplash.com/photo-1486870591958-9b9d0d1dda99?w=800&q=80',
            'mt_3'    => 'https://images.unsplash.com/photo-1454496522488-7a8e488e8606?w=800&q=80',

            // Budaya/Desa
            'culture_1' => 'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?w=800&q=80',
            'culture_2' => 'https://images.unsplash.com/photo-1504898770365-14faca6a7320?w=800&q=80',

            // Hotel — Exterior
            'hotel_ext_1' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80',
            'hotel_ext_2' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&q=80',
            'hotel_ext_3' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800&q=80',

            // Hotel — Room
            'room_1'  => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80',
            'room_2'  => 'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=800&q=80',
            'room_3'  => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&q=80',
            'room_4'  => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80',
            'room_5'  => 'https://images.unsplash.com/photo-1585412727339-54e4bae3bbf9?w=800&q=80',

            // Hotel — Facility
            'pool_1'  => 'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=800&q=80',
            'lobby_1' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&q=80',

            // Transportasi
            'car_1'    => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0afa?w=800&q=80',
            'car_2'    => 'https://images.unsplash.com/photo-1449965408869-ebd13bc9e5a8?w=800&q=80',
            'bus_1'    => 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=800&q=80',
            'bus_2'    => 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=800&q=80',
            'scooter_1' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=800&q=80',
            'scooter_2' => 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=800&q=80',

            // Paket Wisata / Landscape
            'travel_1' => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=800&q=80',
            'travel_2' => 'https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=800&q=80',
            'travel_3' => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80',

            // Kuliner
            'food_1'  => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80',
            'food_2'  => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&q=80',
            'food_3'  => 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=800&q=80',
            'food_4'  => 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=800&q=80',
            'food_5'  => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=800&q=80',
            'food_6'  => 'https://images.unsplash.com/photo-1555126634-323283e090fa?w=800&q=80',

            // Kerajinan / Oleh-oleh
            'craft_1' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&q=80',
            'craft_2' => 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=800&q=80',
            'craft_3' => 'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=800&q=80',

            // Event
            'event_1' => 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=800&q=80',
            'event_2' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&q=80',
            'event_3' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800&q=80',
            'event_4' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&q=80',
            'event_5' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&q=80',
            'event_6' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=800&q=80',
        ];

        $now = now();

        // ============================================================
        // 1. DESTINATION CATEGORIES
        // ============================================================
        $destCats = [
            ['name' => 'Pantai', 'slug' => 'pantai', 'icon' => '🏖️'],
            ['name' => 'Pegunungan', 'slug' => 'pegunungan', 'icon' => '🏔️'],
            ['name' => 'Budaya', 'slug' => 'budaya', 'icon' => '🏛️'],
            ['name' => 'Air Terjun', 'slug' => 'air-terjun', 'icon' => '💧'],
            ['name' => 'Taman Nasional', 'slug' => 'taman-nasional', 'icon' => '🌿'],
        ];
        foreach ($destCats as $c) {
            DB::table('destination_categories')->insertOrIgnore(array_merge($c, [
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }
        $catPantai   = DB::table('destination_categories')->where('slug', 'pantai')->first();
        $catPegunungan = DB::table('destination_categories')->where('slug', 'pegunungan')->first();
        $catBudaya   = DB::table('destination_categories')->where('slug', 'budaya')->first();
        $this->command->info('  ✅ Destination Categories');

        // ============================================================
        // 2. UMKM CATEGORIES
        // ============================================================
        $umkmCats = [
            ['name' => 'Kuliner', 'slug' => 'kuliner', 'icon' => '🍜'],
            ['name' => 'Oleh-oleh', 'slug' => 'oleh-oleh', 'icon' => '🎁'],
            ['name' => 'Kerajinan', 'slug' => 'kerajinan', 'icon' => '🧶'],
        ];
        foreach ($umkmCats as $c) {
            DB::table('umkm_categories')->insertOrIgnore(array_merge($c, [
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }
        $catKuliner  = DB::table('umkm_categories')->where('slug', 'kuliner')->first();
        $catOleh     = DB::table('umkm_categories')->where('slug', 'oleh-oleh')->first();
        $catKerajinan = DB::table('umkm_categories')->where('slug', 'kerajinan')->first();
        $this->command->info('  ✅ UMKM Categories');

        // ============================================================
        // 3. FACILITIES
        // ============================================================
        $facilities = [
            ['name' => 'Toilet', 'icon' => '🚻'],
            ['name' => 'Parkir', 'icon' => '🅿️'],
            ['name' => 'Mushola', 'icon' => '🕌'],
            ['name' => 'WiFi', 'icon' => '📶'],
            ['name' => 'Warung Makan', 'icon' => '🍽️'],
            ['name' => 'Restoran', 'icon' => '🍽️'],
            ['name' => 'Spot Foto', 'icon' => '📸'],
            ['name' => 'Gazebo', 'icon' => '🏠'],
            ['name' => 'Penginapan', 'icon' => '🏨'],
            ['name' => 'Rental Alat', 'icon' => '🤿'],
            ['name' => 'Toko Oleh-oleh', 'icon' => '🛍️'],
        ];
        foreach ($facilities as $f) {
            DB::table('facilities')->insertOrIgnore(array_merge($f, [
                'created_at' => $now, 'updated_at' => $now,
            ]));
        }
        $allFacilityIds = DB::table('facilities')->pluck('id');
        $this->command->info('  ✅ Facilities');

        // ============================================================
        // 4. USERS (Admin, Manager, UMKM, Tourist)
        // ============================================================
        $users = [
            ['email' => 'admin@nusatrip.com',    'name' => 'Admin NusaTrip',        'role' => 'admin',   'status' => 'active'],
            ['email' => 'manager@nusatrip.com',  'name' => 'Manager Lombok',       'role' => 'manager', 'status' => 'active'],
            ['email' => 'umkm@nusatrip.com',     'name' => 'UMKM Demo NusaTrip',   'role' => 'umkm',    'status' => 'active'],
            ['email' => 'tourist@nusatrip.com',  'name' => 'Tourist Test',         'role' => 'tourist', 'status' => 'active'],
        ];
        $userIds = [];
        foreach ($users as $u) {
            $existing = DB::table('users')->where('email', $u['email'])->first();
            if (!$existing) {
                $id = DB::table('users')->insertGetId(array_merge($u, [
                    'password'   => Hash::make('password123'),
                    'phone'      => '0812' . rand(10000000, 99999999),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $userIds[$u['email']] = $id;
            } else {
                $userIds[$u['email']] = $existing->id;
            }
        }
        $adminId   = $userIds['admin@nusatrip.com'];
        $managerId = $userIds['manager@nusatrip.com'];
        $umkmId    = $userIds['umkm@nusatrip.com'];
        $touristId = $userIds['tourist@nusatrip.com'];

        // Wallets
        foreach ($userIds as $uid) {
            DB::table('wallets')->insertOrIgnore([
                'user_id'    => $uid,
                'balance'    => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        // Kasih saldo tourist
        DB::table('wallets')->where('user_id', $touristId)->update(['balance' => 5000.0000]);
        $this->command->info('  ✅ Users & Wallets');

        // ============================================================
        // 5. DESTINATIONS
        // ============================================================
        $destinations = [
            [
                'cat_id' => $catPantai->id, 'name' => 'Pantai Kuta Lombok', 'slug' => 'pantai-kuta-lombok',
                'desc' => 'Pantai Kuta Lombok terkenal dengan pasir putihnya yang memukau dan ombak yang cocok untuk surfing. Pemandangan sunset di sini merupakan salah satu yang terbaik di Indonesia.',
                'address' => 'Kuta, Lombok Tengah, Nusa Tenggara Barat',
                'lat' => -8.9023, 'lng' => 116.2870,
                'open' => '06:00', 'close' => '18:00', 'price' => 15000.00, 'est' => 200000.00,
                'phone' => '0370123456',
                'images' => [$img['beach_1'], $img['beach_2'], $img['beach_3']],
            ],
            [
                'cat_id' => $catPantai->id, 'name' => 'Pantai Pink Lombok', 'slug' => 'pantai-pink-lombok',
                'desc' => 'Salah satu dari tujuh pantai berpasir pink di dunia. Perpaduan pasir merah muda dengan air laut biru jernih menciptakan pemandangan yang sangat eksotis.',
                'address' => 'Desa Tangsi, Jerowaru, Lombok Timur',
                'lat' => -8.8500, 'lng' => 116.5500,
                'open' => '07:00', 'close' => '17:00', 'price' => 25000.00, 'est' => 250000.00,
                'phone' => '0370654321',
                'images' => [$img['beach_4'], $img['beach_5'], $img['beach_6']],
            ],
            [
                'cat_id' => $catPegunungan->id, 'name' => 'Gunung Rinjani', 'slug' => 'gunung-rinjani',
                'desc' => 'Gunung berapi aktif setinggi 3.726 meter ini merupakan gunung tertinggi kedua di Indonesia. Mendaki ke puncaknya dan melihat Danau Segara Anak adalah pengalaman tak terlupakan.',
                'address' => 'Sembalun, Lombok Timur, NTB',
                'lat' => -8.4117, 'lng' => 116.4575,
                'open' => '05:00', 'close' => '17:00', 'price' => 150000.00, 'est' => 1500000.00,
                'phone' => '0370789101',
                'images' => [$img['mt_1'], $img['mt_2'], $img['mt_3']],
            ],
            [
                'cat_id' => $catBudaya->id, 'name' => 'Desa Sade Lombok', 'slug' => 'desa-sade-lombok',
                'desc' => 'Desa adat Sasak yang masih mempertahankan budaya dan arsitektur tradisional. Rumah-rumahnya beratap ilalang dan lantai tanah liat, menunjukkan kearifan lokal suku Sasak.',
                'address' => 'Rembitan, Pujut, Lombok Tengah',
                'lat' => -8.8600, 'lng' => 116.2800,
                'open' => '08:00', 'close' => '17:00', 'price' => 10000.00, 'est' => 100000.00,
                'phone' => '0370111222',
                'images' => [$img['culture_1'], $img['culture_2'], $img['beach_8']],
            ],
            [
                'cat_id' => $catPantai->id, 'name' => 'Pantai Tanjung Aan', 'slug' => 'pantai-tanjung-aan',
                'desc' => 'Dikenal dengan butiran pasirnya yang seperti merica (nyiur), pantai ini memiliki dua teluk dengan karakteristik air laut yang berbeda.',
                'address' => 'Tanjung Aan, Lombok Tengah, NTB',
                'lat' => -8.9100, 'lng' => 116.3000,
                'open' => '06:00', 'close' => '18:00', 'price' => 10000.00, 'est' => 150000.00,
                'phone' => '0370333444',
                'images' => [$img['beach_7'], $img['beach_1'], $img['beach_3']],
            ],
        ];

        $destIds = [];
        foreach ($destinations as $d) {
            $existing = DB::table('destinations')->where('slug', $d['slug'])->first();
            if (!$existing) {
                $did = DB::table('destinations')->insertGetId([
                    'destination_category_id' => $d['cat_id'],
                    'manager_id'             => $managerId,
                    'name'                   => $d['name'],
                    'slug'                   => $d['slug'],
                    'description'            => $d['desc'],
                    'address'                => $d['address'],
                    'latitude'               => $d['lat'],
                    'longitude'              => $d['lng'],
                    'open_hour'              => $d['open'],
                    'close_hour'             => $d['close'],
                    'ticket_price'           => $d['price'],
                    'estimated_cost'         => $d['est'],
                    'phone'                  => $d['phone'],
                    'status'                 => 'published',
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ]);

                // Gallery
                foreach ($d['images'] as $i => $image) {
                    DB::table('destination_galleries')->insert([
                        'destination_id' => $did,
                        'image'          => $image,
                        'caption'        => "{$d['name']} — Foto " . ($i + 1),
                        'sort_order'     => $i,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]);
                }

                // Facilities (random 3-5)
                foreach ($allFacilityIds->random(rand(3, 5)) as $facId) {
                    DB::table('destination_facility')->insertOrIgnore([
                        'destination_id' => $did,
                        'facility_id'    => $facId,
                    ]);
                }

                $destIds[$d['slug']] = $did;
            } else {
                $destIds[$d['slug']] = $existing->id;
            }
        }
        $this->command->info('  ✅ Destinations + Galleries + Facilities');

        // ============================================================
        // 6. UMKM + PRODUCTS
        // ============================================================
        $umkms = [
            ['name' => 'Warung Seafood Pak Budi', 'slug' => 'warung-seafood-pak-budi', 'cat_id' => $catKuliner->id, 'desc' => 'Seafood segar langsung dari nelayan pantai. Ikan bakar dan cumi goreng menjadi andalan.', 'phone' => '0812340001', 'hours' => '10:00 - 22:00', 'img' => $img['food_3']],
            ['name' => 'Kedai Kopi Lombok', 'slug' => 'kedai-kopi-lombok', 'cat_id' => $catKuliner->id, 'desc' => 'Kopi robusta khas Lombok yang dipanggang tradisional. Suasana santai dengan pemandangan sawah.', 'phone' => '0812340002', 'hours' => '08:00 - 21:00', 'img' => $img['food_5']],
            ['name' => 'Toko Oleh-Oleh Mutiara', 'slug' => 'toko-oleh-oleh-mutiara', 'cat_id' => $catOleh->id, 'desc' => 'Mutiara asli Lombok, manik-manik, dan suvenir khas Sasak.', 'phone' => '0812340003', 'hours' => '09:00 - 20:00', 'img' => $img['craft_1']],
            ['name' => 'Tenun Sukarare', 'slug' => 'tenun-sukarare', 'cat_id' => $catKerajinan->id, 'desc' => 'Kain tenun tradisional khas Suku Sasak yang ditenun manual dengan motif unik.', 'phone' => '0812340004', 'hours' => '08:00 - 17:00', 'img' => $img['craft_2']],
            ['name' => 'Ayam Taliwang Bu Ani', 'slug' => 'ayam-taliwang-bu-ani', 'cat_id' => $catKuliner->id, 'desc' => 'Ayam taliwang legendaris dengan bumbu khas yang pedas dan nasi hangat.', 'phone' => '0812340005', 'hours' => '11:00 - 21:00', 'img' => $img['food_4']],
        ];

        $productsPool = [
            ['name' => 'Ikan Bakar Segar', 'price' => 50000, 'unit' => 'porsi', 'img' => $img['food_1']],
            ['name' => 'Cumi Goreng Tepung', 'price' => 35000, 'unit' => 'porsi', 'img' => $img['food_3']],
            ['name' => 'Kopi Lombok 200gr', 'price' => 45000, 'unit' => 'pack', 'img' => $img['food_5']],
            ['name' => 'Kalung Mutiara Air Tawar', 'price' => 150000, 'unit' => 'pcs', 'img' => $img['craft_1']],
            ['name' => 'Kain Tenun 2 Meter', 'price' => 250000, 'unit' => 'meter', 'img' => $img['craft_2']],
            ['name' => 'Ayam Taliwang', 'price' => 40000, 'unit' => 'porsi', 'img' => $img['food_4']],
            ['name' => 'Plecing Kangkung', 'price' => 15000, 'unit' => 'porsi', 'img' => $img['food_6']],
            ['name' => 'Gelang Manik-Manik', 'price' => 25000, 'unit' => 'pcs', 'img' => $img['craft_3']],
        ];

        $umkmIdList = [];
        foreach ($umkms as $u) {
            $existing = DB::table('umkms')->where('slug', $u['slug'])->first();
            if (!$existing) {
                $destId = $destIds[array_rand($destIds)];
                $uid = DB::table('umkms')->insertGetId([
                    'user_id'            => $umkmId,
                    'destination_id'     => $destId,
                    'umkm_category_id'   => $u['cat_id'],
                    'name'               => $u['name'],
                    'slug'               => $u['slug'],
                    'description'        => $u['desc'],
                    'address'            => 'Sekitar destinasi',
                    'latitude'           => -8.85 + rand(-50, 50) / 1000,
                    'longitude'          => 116.30 + rand(-50, 50) / 1000,
                    'phone'              => $u['phone'],
                    'opening_hours'      => $u['hours'],
                    'status'             => 'active',
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);

                // Products (random 2-3 per UMKM)
                $picked = collect($productsPool)->random(rand(2, 3));
                foreach ($picked as $prod) {
                    $pid = DB::table('products')->insertGetId([
                        'umkm_id'     => $uid,
                        'name'        => $prod['name'],
                        'slug'        => Str::slug($prod['name']) . '-' . $uid,
                        'description' => 'Produk berkualitas dari UMKM lokal Lombok.',
                        'price'       => $prod['price'],
                        'stock'       => rand(10, 50),
                        'unit'        => $prod['unit'],
                        'image'       => $prod['img'],
                        'status'      => 'available',
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                    // Product image
                    DB::table('product_images')->insert([
                        'product_id' => $pid,
                        'image'      => $prod['img'],
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $umkmIdList[] = $uid;
            } else {
                $umkmIdList[] = $existing->id;
            }
        }
        $this->command->info('  ✅ UMKM + Products');

        // ============================================================
        // 7. EVENTS
        // ============================================================
        $events = [
            ['dest_slug' => 'pantai-kuta-lombok', 'title' => 'Festival Surfing Kuta Lombok 2026', 'slug' => 'festival-surfing-kuta-lombok-2026', 'desc' => 'Event tahunan yang mempertemukan peselancar profesional dari seluruh dunia. Selain kompetisi, ada juga workshop surfing untuk pemula, live music di pinggir pantai, dan bazaar kuliner laut lokal.', 'start' => $now->copy()->addDays(15), 'end' => $now->copy()->addDays(18), 'loc' => 'Pantai Kuta, Lombok Tengah', 'img' => $img['event_1'], 'status' => 'upcoming'],
            ['dest_slug' => 'pantai-pink-lombok', 'title' => 'Pink Beach Marathon', 'slug' => 'pink-beach-marathon', 'desc' => 'Lari maraton pertama di atas pasir pink! Tersedia kategori 5K, 10K, dan Half Marathon. Peserta mendapatkan medali eksklusif berbentuk kelopak pink.', 'start' => $now->copy()->addMonths(2), 'end' => $now->copy()->addMonths(2)->addHours(8), 'loc' => 'Pantai Pink, Lombok Timur', 'img' => $img['event_3'], 'status' => 'upcoming'],
            ['dest_slug' => 'gunung-rinjani', 'title' => 'Rinjani Summit Expedition', 'slug' => 'rinjani-summit-expedition', 'desc' => 'Ekspedisi terbuka untuk pendaki pemula dan menengah menuju puncak Gunung Rinjani. Fasilitas termasuk porter berpengalaman dan peralatan camping lengkap.', 'start' => $now->copy()->subDays(5), 'end' => $now->copy()->subDays(2), 'loc' => 'Sembalun, Lombok Timur', 'img' => $img['mt_1'], 'status' => 'finished'],
            ['dest_slug' => 'desa-sade-lombok', 'title' => 'Festival Budaya Sasak Sade', 'slug' => 'festival-budaya-sasak-sade', 'desc' => 'Rasakan kehidupan autentik suku Sasak! Tarian tradisional, prosesi tenun kain ikat, upacara adat, dan kuliner khas Lombok.', 'start' => $now, 'end' => $now->copy()->addDays(3), 'loc' => 'Desa Sade, Rembitan', 'img' => $img['event_5'], 'status' => 'ongoing'],
            ['dest_slug' => 'pantai-tanjung-aan', 'title' => 'Tanjung Aan Beach Camp & Music', 'slug' => 'tanjung-aan-beach-camp-music', 'desc' => 'Camping mewah di tepi pantai ditemani alunan musik akustik indie. Tiket termasuk tenda glamping, BBQ dinner, dan akses free flow minuman.', 'start' => $now->copy()->addDays(30), 'end' => $now->copy()->addDays(32), 'loc' => 'Pantai Tanjung Aan', 'img' => $img['event_2'], 'status' => 'upcoming'],
        ];
        $eventGalleryPool = [$img['event_1'], $img['event_2'], $img['event_3'], $img['event_4'], $img['event_5'], $img['event_6']];

        foreach ($events as $e) {
            $existing = DB::table('events')->where('slug', $e['slug'])->first();
            if (!$existing) {
                $eid = DB::table('events')->insertGetId([
                    'destination_id' => $destIds[$e['dest_slug']],
                    'created_by'     => $adminId,
                    'title'          => $e['title'],
                    'slug'           => $e['slug'],
                    'description'    => $e['desc'],
                    'start_date'     => $e['start'],
                    'end_date'       => $e['end'],
                    'location'       => $e['loc'],
                    'image'          => $e['img'],
                    'status'         => $e['status'],
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);

                foreach (collect($eventGalleryPool)->shuffle()->take(rand(3, 5)) as $gi => $gimg) {
                    DB::table('event_galleries')->insert([
                        'event_id'   => $eid,
                        'image'      => $gimg,
                        'caption'    => "Dokumentasi {$e['title']} — " . ($gi + 1),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
        $this->command->info('  ✅ Events + Galleries');

        // ============================================================
        // 8. HOTELS
        // ============================================================
        $hotels = [
            [
                'name' => 'Kuta Lombok Beach Resort', 'slug' => 'kuta-lombok-beach-resort',
                'dest_slug' => 'pantai-kuta-lombok',
                'desc' => 'Resort bintang 4 dengan pemandangan langsung ke Pantai Kuta. Dilengkapi kolam renang infinity edge, restoran seafood, dan spa. Cocok untuk liburan keluarga dan pasangan.',
                'address' => 'Jl. Raya Kuta No. 88, Lombok Tengah',
                'lat' => -8.9050, 'lng' => 116.2900,
                'stars' => 4, 'phone' => '0370-456789', 'web' => 'kutalombokresort.id',
                'check_in' => '14:00:00', 'check_out' => '12:00:00',
                'thumb' => $img['hotel_ext_1'],
                'galleries' => [$img['hotel_ext_1'], $img['pool_1'], $img['lobby_1'], $img['room_1']],
                'rooms' => [
                    ['name' => 'Standard Room', 'cap' => 2, 'price' => 450000, 'total' => 10, 'amenities' => ['AC', 'TV 32"', 'WiFi', 'Kamar Mandi Dalam'], 'img' => $img['room_4']],
                    ['name' => 'Deluxe Room', 'cap' => 2, 'price' => 750000, 'total' => 8, 'amenities' => ['AC', 'TV 42"', 'WiFi', 'Balkon Laut', 'Bathub'], 'img' => $img['room_1']],
                    ['name' => 'Family Suite', 'cap' => 4, 'price' => 1200000, 'total' => 4, 'amenities' => ['AC', 'TV 55"', 'WiFi', 'Ruang Tamu', 'Balkon Luas', 'Bathub'], 'img' => $img['room_3']],
                ],
            ],
            [
                'name' => 'Sembalun Valley Lodge', 'slug' => 'sembalun-valley-lodge',
                'dest_slug' => 'gunung-rinjani',
                'desc' => 'Lodge nyaman di kaki Gunung Rinjani dengan pemandangan sawah dan perbukitan hijau. Base camp ideal sebelum mendaki. Suasana sejuk dan tenang.',
                'address' => 'Sembalun Lawang, Lombok Timur',
                'lat' => -8.4000, 'lng' => 116.4600,
                'stars' => 3, 'phone' => '0370-567890', 'web' => null,
                'check_in' => '13:00:00', 'check_out' => '11:00:00',
                'thumb' => $img['hotel_ext_3'],
                'galleries' => [$img['hotel_ext_3'], $img['travel_2'], $img['room_2']],
                'rooms' => [
                    ['name' => 'Kamar Twin', 'cap' => 2, 'price' => 250000, 'total' => 12, 'amenities' => ['AC', 'WiFi', 'Pemandangan Gunung'], 'img' => $img['room_2']],
                    ['name' => 'Kamar Quad', 'cap' => 4, 'price' => 400000, 'total' => 6, 'amenities' => ['AC', 'WiFi', 'Ruang Tamu Kecil', 'Pemandangan Gunung'], 'img' => $img['room_5']],
                ],
            ],
            [
                'name' => 'Tanjung Aan Homestay', 'slug' => 'tanjung-aan-homestay',
                'dest_slug' => 'pantai-tanjung-aan',
                'desc' => 'Homestay ekonomis 100 meter dari Pantai Tanjung Aan. Bersih, nyaman, dan ramah di kantong. Pilihan tepat untuk backpacker.',
                'address' => 'Tanjung Aan, Lombok Tengah',
                'lat' => -8.9120, 'lng' => 116.3050,
                'stars' => 2, 'phone' => '0370-678901', 'web' => null,
                'check_in' => '13:00:00', 'check_out' => '10:00:00',
                'thumb' => $img['hotel_ext_2'],
                'galleries' => [$img['hotel_ext_2'], $img['room_4'], $img['beach_5']],
                'rooms' => [
                    ['name' => 'Kamar Fan', 'cap' => 2, 'price' => 100000, 'total' => 8, 'amenities' => ['Kipas Angin', 'WiFi'], 'img' => $img['room_5']],
                    ['name' => 'Kamar AC', 'cap' => 2, 'price' => 175000, 'total' => 6, 'amenities' => ['AC', 'WiFi', 'TV'], 'img' => $img['room_4']],
                ],
            ],
        ];

        $hotelIds = [];
        foreach ($hotels as $h) {
            $existing = DB::table('hotels')->where('slug', $h['slug'])->first();
            if (!$existing) {
                $hid = DB::table('hotels')->insertGetId([
                    'manager_id'     => $managerId,
                    'destination_id' => $destIds[$h['dest_slug']],
                    'name'           => $h['name'],
                    'slug'           => $h['slug'],
                    'description'    => $h['desc'],
                    'address'        => $h['address'],
                    'latitude'       => $h['lat'],
                    'longitude'      => $h['lng'],
                    'star_rating'    => $h['stars'],
                    'phone'          => $h['phone'],
                    'website'        => $h['web'],
                    'check_in_time'  => $h['check_in'],
                    'check_out_time' => $h['check_out'],
                    'thumbnail'      => $h['thumb'],
                    'status'         => 'published',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);

                // Galleries
                foreach ($h['galleries'] as $i => $gimg) {
                    DB::table('hotel_galleries')->insert([
                        'hotel_id'   => $hid,
                        'image'      => $gimg,
                        'caption'    => "{$h['name']} — Foto " . ($i + 1),
                        'sort_order' => $i,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // Rooms
                foreach ($h['rooms'] as $r) {
                    DB::table('hotel_rooms')->insert([
                        'hotel_id'       => $hid,
                        'name'           => $r['name'],
                        'description'    => "Kamar {$r['name']} di {$h['name']}. Kapasitas {$r['cap']} orang.",
                        'capacity'       => $r['cap'],
                        'price_per_night'=> $r['price'],
                        'total_rooms'    => $r['total'],
                        'amenities'      => json_encode($r['amenities']),
                        'status'         => 'available',
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]);
                }

                $hotelIds[$h['slug']] = $hid;
            } else {
                $hotelIds[$h['slug']] = $existing->id;
            }
        }
        $this->command->info('  ✅ Hotels + Rooms + Galleries');

        // ============================================================
        // 9. TRANSPORTATIONS
        // ============================================================
        $transports = [
            [
                'name' => 'Avanza Lombok Explorer', 'slug' => 'avanza-lombok-explorer',
                'dest_slug' => 'pantai-kuta-lombok',
                'type' => 'car', 'desc' => 'Toyota Avanza silver 2024, bersih dan terawat. Cocok untuk rombongan kecil hingga 6 orang. Supir berpengalaman mengenal semua rute di Lombok.',
                'cap' => 6, 'price' => 500000, 'driver' => true, 'fuel' => false,
                'thumb' => $img['car_1'],
                'galleries' => [$img['car_1'], $img['car_2']],
            ],
            [
                'name' => 'HiAce Lombok Tour', 'slug' => 'hiace-lombok-tour',
                'dest_slug' => 'pantai-kuta-lombok',
                'type' => 'bus', 'desc' => 'Toyota HiAce Commuter kapasitas besar, ideal untuk rombongan 12-15 orang. AC double blower, bagasi luas, dan supir profesional.',
                'cap' => 15, 'price' => 1200000, 'driver' => true, 'fuel' => false,
                'thumb' => $img['bus_1'],
                'galleries' => [$img['bus_1'], $img['bus_2']],
            ],
            [
                'name' => 'Vespa Lombok Classic', 'slug' => 'vespa-lombok-classic',
                'dest_slug' => 'pantai-tanjung-aan',
                'type' => 'motorcycle', 'desc' => 'Vespa classic untuk explore sekitar pantai dengan gaya. Tanpa supir — butuh SIM C. Bensin ditanggung penyewa.',
                'cap' => 2, 'price' => 150000, 'driver' => false, 'fuel' => false,
                'thumb' => $img['scooter_1'],
                'galleries' => [$img['scooter_1'], $img['scooter_2']],
            ],
        ];

        $transportIds = [];
        foreach ($transports as $t) {
            $existing = DB::table('transportations')->where('slug', $t['slug'])->first();
            if (!$existing) {
                $tid = DB::table('transportations')->insertGetId([
                    'manager_id'      => $managerId,
                    'destination_id'  => $destIds[$t['dest_slug']],
                    'name'            => $t['name'],
                    'slug'            => $t['slug'],
                    'type'            => $t['type'],
                    'description'     => $t['desc'],
                    'capacity'        => $t['cap'],
                    'price_per_day'   => $t['price'],
                    'includes_driver' => $t['driver'],
                    'includes_fuel'   => $t['fuel'],
                    'thumbnail'       => $t['thumb'],
                    'phone'           => '0812' . rand(10000000, 99999999),
                    'status'          => 'published',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

                foreach ($t['galleries'] as $i => $gimg) {
                    DB::table('transportation_galleries')->insert([
                        'transportation_id' => $tid,
                        'image'             => $gimg,
                        'caption'           => "{$t['name']} — Foto " . ($i + 1),
                        'sort_order'        => $i,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);
                }

                $transportIds[$t['slug']] = $tid;
            } else {
                $transportIds[$t['slug']] = $existing->id;
            }
        }
        $this->command->info('  ✅ Transportations + Galleries');

        // ============================================================
        // 10. TRAVEL PACKAGES + SCHEDULES
        // ============================================================
               $packages = [
            [
                'name' => 'Lombok Explorer 3H2M Premium',
                'slug' => 'lombok-explorer-3h2m-premium',
                'dest_slug' => 'pantai-kuta-lombok',
                'hotel_slug' => 'kuta-lombok-beach-resort',
                'transport_slug' => 'hiace-lombok-tour',
                'desc' => 'Paket wisata lengkap eksplorasi Lombok selama 3 hari 2 malam. Termasuk hotel bintang 4, transportasi HiAce, tiket masuk 3 destinasi utama, dan makan. Pengalaman Lombok tanpa repot.',
                'thumb' => $img['travel_1'],
                'days' => 3,
                'nights' => 2,
                'price' => 2500000,
                'max_pax' => 15,
                'included' => ['Transportasi HiAce + Supir', 'Hotel Kuta Lombok Beach Resort 2 malam (Deluxe Room)', 'Tiket Pantai Kuta Lombok', 'Tiket Pantai Pink Lombok', 'Tiket Desa Sade Lombok', 'Sarapan 2x, Makan Siang 3x'],
                'excluded' => ['Bahan bakar kendaraan', 'Pengeluaran pribadi', 'Tip supir'],
                'meals' => ['breakfast' => 2, 'lunch' => 3],
                'terms' => 'Pembatalan H-7: refund 50%. Pembatalan H-3: tidak ada refund. Wajib membawa KTP.',
                'galleries' => [$img['travel_1'], $img['beach_1'], $img['hotel_ext_1'], $img['mt_1']],
                'schedules' => [
                    [
                        'dep' => '2026-08-10',
                        'ret' => '2026-08-12',
                        'pickup' => 'Halaman Parkir Bandara Lombok International',
                        'pickup_time' => '07:00:00',
                        'vehicle' => 'Toyota HiAce Putih · N 1234 AB',
                        'driver' => 'Pak Ahmad',
                        'driver_phone' => '081234567001',
                    ],
                    [
                        'dep' => '2026-08-25',
                        'ret' => '2026-08-27',
                        'pickup' => 'Halaman Parkir Bandara Lombok International',
                        'pickup_time' => '07:00:00',
                        'vehicle' => 'Toyota HiAce Silver · N 5678 CD',
                        'driver' => 'Pak Budi',
                        'driver_phone' => '081234567002',
                    ],
                    [
                        'dep' => '2026-09-10',
                        'ret' => '2026-09-12',
                        'pickup' => 'Halaman Parkir Bandara Lombok International',
                        'pickup_time' => '07:00:00',
                        'vehicle' => 'Toyota HiAce Putih · N 9012 EF',
                        'driver' => 'Pak Cahyo',
                        'driver_phone' => '081234567003',
                    ],
                ],
            ],
            [
                'name' => 'Lombok Beach Hopping 1D',
                'slug' => 'lombok-beach-hopping-1d',
                'dest_slug' => 'pantai-kuta-lombok',
                'hotel_slug' => null,
                'transport_slug' => 'avanza-lombok-explorer',
                'desc' => 'Day trip hemat mengunjungi 3 pantai terindah Lombok dalam sehari. Sudah termasuk transport dan tiket masuk. Minimal 2 orang.',
                'thumb' => $img['travel_3'],
                'days' => 1,
                'nights' => 0,
                'price' => 350000,
                'max_pax' => 6,
                'included' => ['Transportasi Avanza + Supir', 'Tiket Pantai Kuta', 'Tiket Pantai Tanjung Aan', 'Tiket Pantai Pink', 'Makan siang 1x'],
                'excluded' => ['Bahan bakar', 'Pengeluaran pribadi'],
                'meals' => ['lunch' => 1],
                'terms' => 'Minimal 2 orang. Pembatalan H-1: tidak ada refund.',
                'galleries' => [$img['travel_3'], $img['beach_4'], $img['beach_7']],
                'schedules' => [
                    [
                        'dep' => '2026-08-05',
                        'ret' => '2026-08-05',
                        'pickup' => 'Depan Hotel Kuta Lombok Beach Resort',
                        'pickup_time' => '08:00:00',
                        'vehicle' => 'Toyota Avanza Silver · N 3456 GH',
                        'driver' => 'Pak Dedi',
                        'driver_phone' => '081234567004',
                    ],
                    [
                        'dep' => '2026-08-12',
                        'ret' => '2026-08-12',
                        'pickup' => 'Depan Hotel Kuta Lombok Beach Resort',
                        'pickup_time' => '08:00:00',
                        'vehicle' => 'Toyota Avanza Silver · N 3456 GH',
                        'driver' => 'Pak Dedi',
                        'driver_phone' => '081234567004',
                    ],
                    [
                        'dep' => '2026-08-19',
                        'ret' => '2026-08-19',
                        'pickup' => 'Depan Hotel Kuta Lombok Beach Resort',
                        'pickup_time' => '08:00:00',
                        'vehicle' => 'Toyota Avanza Silver · N 3456 GH',
                        'driver' => 'Pak Dedi',
                        'driver_phone' => '081234567004',
                    ],
                    [
                        'dep' => '2026-08-26',
                        'ret' => '2026-08-26',
                        'pickup' => 'Depan Hotel Kuta Lombok Beach Resort',
                        'pickup_time' => '08:00:00',
                        'vehicle' => 'Toyota Avanza Silver · N 3456 GH',
                        'driver' => 'Pak Dedi',
                        'driver_phone' => '081234567004',
                    ],
                ],
            ],
        ];

        foreach ($packages as $p) {
            $existing = DB::table('travel_packages')->where('slug', $p['slug'])->first();
            if (!$existing) {
                $pid = DB::table('travel_packages')->insertGetId([
                    'manager_id'         => $managerId,
                    'destination_id'     => $destIds[$p['dest_slug']],
                    'hotel_id'           => $p['hotel_slug'] ? $hotelIds[$p['hotel_slug']] : null,
                    'transportation_id'  => $p['transport_slug'] ? $transportIds[$p['transport_slug']] : null,
                    'name'               => $p['name'],
                    'slug'               => $p['slug'],
                    'description'        => $p['desc'],
                    'thumbnail'          => $p['thumb'],
                    'duration_days'      => $p['days'],
                    'duration_nights'    => $p['nights'],
                    'price_per_person'   => $p['price'],
                    'max_travelers'      => $p['max_pax'],
                    'included_items'     => json_encode($p['included']),
                    'excluded_items'     => json_encode($p['excluded']),
                    'meals_included'     => json_encode($p['meals']),
                    'terms_conditions'   => $p['terms'],
                    'status'             => 'published',
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);

                // Galleries
                foreach ($p['galleries'] as $i => $gimg) {
                    DB::table('travel_package_galleries')->insert([
                        'travel_package_id' => $pid,
                        'image'             => $gimg,
                        'caption'           => "{$p['name']} — Foto " . ($i + 1),
                        'sort_order'        => $i,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);
                }

                // Schedules
                foreach ($p['schedules'] as $s) {
                    DB::table('travel_package_schedules')->insertOrIgnore([
                        'travel_package_id' => $pid,
                        'departure_date'    => $s['dep'],
                        'return_date'       => $s['ret'],
                        'max_capacity'      => $p['max_pax'],
                        'current_booked'    => 0,
                        'status'            => 'available',
                        'pickup_location'   => $s['pickup'] ?? null,
                        'pickup_time'       => $s['pickup_time'] ?? null,
                        'vehicle_info'      => $s['vehicle'] ?? null,
                        'driver_name'       => $s['driver'] ?? null,
                        'driver_phone'      => $s['driver_phone'] ?? null,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);
                }
            }
        }

        $this->command->info('  ✅ Travel Packages + Schedules + Galleries');

        // ============================================================
        // DONE
        // ============================================================
        $this->command->newLine();
        $this->command->info('════════════════════════════════════════');
        $this->command->info('  Semua data test berhasil dibuat!');
        $this->command->info('════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('Akun test:');
        $this->command->info('  Tourist  : tourist@nusatrip.com / password123');
        $this->command->info('  UMKM     : umkm@nusatrip.com / password123');
        $this->command->info('  Manager  : manager@nusatrip.com / password123');
        $this->command->info('  Admin    : admin@nusatrip.com / password123');
        $this->command->newLine();
        $this->command->info('Wallet Tourist: 5.000 Coin (≈ Rp 10.000.000)');
    }
}
