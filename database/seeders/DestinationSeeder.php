<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. BUAT KATEGORI DULU
        $categories = [
            ['name' => 'Pantai', 'slug' => 'pantai', 'icon' => '🏖️'],
            ['name' => 'Pegunungan', 'slug' => 'pegunungan', 'icon' => '🏔️'],
            ['name' => 'Budaya', 'slug' => 'budaya', 'icon' => '🏛️'],
            ['name' => 'Air Terjun', 'slug' => 'air-terjun', 'icon' => '💧'],
            ['name' => 'Taman Nasional', 'slug' => 'taman-nasional', 'icon' => '🌿'],
        ];

        foreach ($categories as $cat) {
            $cat['created_at'] = now();
            $cat['updated_at'] = now();
            DB::table('destination_categories')->insert($cat);
        }

        // 2. BUAT FASILITAS
        $facilities = [
            ['name' => 'Toilet', 'icon' => '🚻'],
            ['name' => 'Parkir', 'icon' => '🅿️'],
            ['name' => 'Mushola', 'icon' => '🕌'],
            ['name' => 'Warung Makan', 'icon' => '🍽️'],
            ['name' => 'Spot Foto', 'icon' => '📸'],
            ['name' => 'Gazebo', 'icon' => '🏠'],
            ['name' => 'Penginapan', 'icon' => '🏨'],
            ['name' => 'Rental Alat', 'icon' => '🤿'],
        ];

        foreach ($facilities as $fac) {
            $fac['created_at'] = now();
            $fac['updated_at'] = now();
            DB::table('facilities')->insert($fac);
        }

        // 3. BUAT DESTINASI
        $destinations = [
            [
                'destination_category_id' => 1,
                'name' => 'Pantai Kuta Lombok',
                'slug' => 'pantai-kuta-lombok',
                'description' => 'Pantai Kuta Lombok terkenal dengan pasir putihnya yang memukau dan ombak yang cocok untuk surfing. Pemandangan sunset di sini merupakan salah satu yang terbaik di Indonesia.',
                'address' => 'Kuta, Lombok Tengah, Nusa Tenggara Barat',
                'latitude' => -8.9023,
                'longitude' => 116.2870,
                'open_hour' => '06:00',
                'close_hour' => '18:00',
                'ticket_price' => 15000.00,
                'estimated_cost' => 200000.00,
                'phone' => '0370123456',
                'status' => 'published',
            ],
            [
                'destination_category_id' => 1,
                'name' => 'Pantai Pink Lombok',
                'slug' => 'pantai-pink-lombok',
                'description' => 'Salah satu dari tujuh pantai berpasir pink di dunia. Perpaduan pasir merah muda dengan air laut biru jernih menciptakan pemandangan yang sangat eksotis.',
                'address' => 'Desa Tangsi, Jerowaru, Lombok Timur',
                'latitude' => -8.8500,
                'longitude' => 116.5500,
                'open_hour' => '07:00',
                'close_hour' => '17:00',
                'ticket_price' => 25000.00,
                'estimated_cost' => 250000.00,
                'phone' => '0370654321',
                'status' => 'published',
            ],
            [
                'destination_category_id' => 2,
                'name' => 'Gunung Rinjani',
                'slug' => 'gunung-rinjani',
                'description' => 'Gunung berapi aktif setinggi 3.726 meter ini merupakan gunung tertinggi kedua di Indonesia. Mendaki ke puncaknya dan melihat Danau Segara Anak adalah pengalaman tak terlupakan.',
                'address' => 'Sembalun, Lombok Timur, NTB',
                'latitude' => -8.4117,
                'longitude' => 116.4575,
                'open_hour' => '05:00',
                'close_hour' => '17:00',
                'ticket_price' => 150000.00,
                'estimated_cost' => 1500000.00,
                'phone' => '0370789101',
                'status' => 'published',
            ],
            [
                'destination_category_id' => 3,
                'name' => 'Desa Sade Lombok',
                'slug' => 'desa-sade-lombok',
                'description' => 'Desa adat Sasak yang masih mempertahankan budaya dan arsitektur tradisional. Rumah-rumahnya beratap ilalang dan lantai tanah liat, menunjukkan kearifan lokal suku Sasak.',
                'address' => 'Rembitan, Pujut, Lombok Tengah',
                'latitude' => -8.8600,
                'longitude' => 116.2800,
                'open_hour' => '08:00',
                'close_hour' => '17:00',
                'ticket_price' => 10000.00,
                'estimated_cost' => 100000.00,
                'phone' => '0370111222',
                'status' => 'published',
            ],
            [
                'destination_category_id' => 1,
                'name' => 'Pantai Tanjung Aan',
                'slug' => 'pantai-tanjung-aan',
                'description' => 'Dikenal dengan butiran pasirnya yang seperti merica (nyiur), pantai ini memiliki dua teluk dengan karakteristik air laut yang berbeda.',
                'address' => 'Tanjung Aan, Lombok Tengah, NTB',
                'latitude' => -8.9100,
                'longitude' => 116.3000,
                'open_hour' => '06:00',
                'close_hour' => '18:00',
                'ticket_price' => 10000.00,
                'estimated_cost' => 150000.00,
                'phone' => '0370333444',
                'status' => 'published',
            ],
        ];

        $destinationIds = [];
        foreach ($destinations as $dest) {
            $dest['manager_id'] = null;
            $dest['created_at'] = now();
            $dest['updated_at'] = now();
            $destinationIds[] = DB::table('destinations')->insertGetId($dest);
        }

        // 4. BUAT GALLERY DESTINASI
        $galleryImages = [
            'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&q=80',
            'https://images.unsplash.com/photo-1519046904884-53103b34b206?w=800&q=80',
            'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80',
            'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&q=80',
            'https://images.unsplash.com/photo-1468413253725-0d5181091126?w=800&q=80',
        ];

        foreach ($destinationIds as $index => $destId) {
            for ($i = 0; $i < 3; $i++) {
                DB::table('destination_galleries')->insert([
                    'destination_id' => $destId,
                    'image' => $galleryImages[$index],
                    'caption' => 'Foto ' . ($i + 1) . ' - ' . $destinations[$index]['name'],
                    'sort_order' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 5. ATTACH FASILITAS
        $facilityIds = DB::table('facilities')->pluck('id');
        
        foreach ($destinationIds as $destId) {
            $randomFacilities = $facilityIds->random(rand(3, 5));
            foreach ($randomFacilities as $facId) {
                DB::table('destination_facility')->insert([
                    'destination_id' => $destId,
                    'facility_id' => $facId,
                ]);
            }
        }

        // ==========================================
        // 6. BUAT DATA EVENT & EVENT GALLERY
        // ==========================================
        
        // Ambil ID User pertama sebagai pembuat event (Admin)
       $creator = User::firstOrCreate(
            ['email' => 'admin@nusatrip.com'],
            [
                'name' => 'Admin NusaTrip',
                'password' => bcrypt('password123'), // Ganti kalau mau
            ]
        );
        $creatorId = $creator->id;

        // Gambar Unsplash khusus untuk Event (Festival, Budaya, Alam)
        $eventImages = [
            'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=800&q=80', // Festival budaya
            'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&q=80', // Konser musik
            'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800&q=80', // Camping/Hiking
            'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&q=80', // Pesta kembang api
            'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&q=80', // Festival makanan
            'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=800&q=80', // Road trip/Jeep
        ];

        // Data Event (Diambil dari destinasi yang sudah ada)
        $events = [
            [
                'destination_id' => $destinationIds[0], // Pantai Kuta
                'title' => 'Festival Surfing Kuta Lombok 2024',
                'slug' => 'festival-surfing-kuta-lombok-2024',
                'description' => "Event tahunan yang mempertemukan peselancar profesional dari seluruh dunia. Selain kompetisi, ada juga workshop surfing untuk pemula, live music di pinggir pantai, dan bazaar kuliner laut lokal. Saksikan keindahan ombak Kuta yang memukau sambil menikmati suasana festival yang meriah!",
                'start_date' => now()->addDays(15),
                'end_date' => now()->addDays(18),
                'location' => 'Pantai Kuta, Lombok Tengah',
                'image' => $eventImages[0],
                'status' => 'upcoming',
            ],
            [
                'destination_id' => $destinationIds[1], // Pantai Pink
                'title' => 'Pink Beach Marathon',
                'slug' => 'pink-beach-marathon',
                'description' => "Lari maraton pertama di atas pasir pink! Tersedia kategori 5K, 10K, dan Half Marathon. Semua jalur menampilkan pemandangan laut biru dan hamparan pasir merah muda yang langka. Peserta juga mendapatkan medali eksklusif berbentuk kelopak pink dan goodie box spesial.",
                'start_date' => now()->addMonths(2),
                'end_date' => now()->addMonths(2)->addHours(8),
                'location' => 'Pantai Pink, Lombok Timur',
                'image' => $eventImages[2],
                'status' => 'upcoming',
            ],
            [
                'destination_id' => $destinationIds[2], // Rinjani
                'title' => 'Rinjani Summit Expedition',
                'slug' => 'rinjani-summit-expedition',
                'description' => "Ekspedisi terbuka untuk pendaki pemula dan menengah menuju puncak Gunung Rinjani. Fasilitas termasuk porter berpengalaman, peralatan camping lengkap, dan tim medis. Kita akan bermalam di tepi Danau Segara Anak dan menyaksikan sunrise spektakuler dari puncak pada esok harinya.",
                'start_date' => now()->subDays(5), // Sudah lewat (Finished)
                'end_date' => now()->subDays(2),
                'location' => 'Sembalun, Lombok Timur',
                'image' => $eventImages[2],
                'status' => 'finished',
            ],
            [
                'destination_id' => $destinationIds[3], // Desa Sade
                'title' => 'Festival Budaya Sasak Sade',
                'slug' => 'festival-budaya-sasak-sade',
                'description' => "Rasakan kehidupan autentik suku Sasak! Event ini menampilkan tarian tradisional, prosesi tenun kain ikat, upacara adat, dan kuliner khas Lombok yang bisa kamu cicipi langsung di halaman rumah warga. Tur ini dipandu langsung oleh tetua adat setempat.",
                'start_date' => now(), // Hari ini (Ongoing)
                'end_date' => now()->addDays(3),
                'location' => 'Desa Sade, Rembitan',
                'image' => $eventImages[4],
                'status' => 'ongoing',
            ],
            [
                'destination_id' => $destinationIds[4], // Tanjung Aan
                'title' => 'Tanjung Aan Beach Camp & Music',
                'slug' => 'tanjung-aan-beach-camp-music',
                'description' => "Camping mewah di tepi pantai ditemani alunan musik akustik indie. Tiket termasuk tenda glamping untuk 2 orang, BBQ dinner, snack box, dan akses free flow minuman. Acara dimulai dari sunset hingga larut malam dengan bintang-bintang sebagai langit atap kita.",
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(32),
                'location' => 'Pantai Tanjung Aan',
                'image' => $eventImages[1],
                'status' => 'upcoming',
            ],
        ];

        $eventIds = [];
        foreach ($events as $eventData) {
            $eventData['created_by'] = $creatorId;
            $eventData['created_at'] = now();
            $eventData['updated_at'] = now();
            
            $eventIds[] = DB::table('events')->insertGetId($eventData);
        }

        // Buat Galeri untuk Setiap Event (3-5 foto per event)
        foreach ($eventIds as $index => $eventId) {
            // Acak gambar dari array eventImages untuk gallery
            $shuffledImages = collect($eventImages)->shuffle()->take(rand(3, 5));
            
            foreach ($shuffledImages as $i => $img) {
                DB::table('event_galleries')->insert([
                    'event_id' => $eventId,
                    'image' => $img,
                    'caption' => "Dokumentasi {$events[$index]['title']} - Bagian " . ($i + 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✅ Destinasi, Event, beserta galerinya berhasil dibuat!');
    }
}