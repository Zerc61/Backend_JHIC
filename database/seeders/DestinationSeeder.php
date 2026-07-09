<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = [
            [
                'destination_category_id' => 1, // Pantai
                'name' => 'Pantai Kuta Lombok',
                'slug' => 'pantai-kuta-lombok',
                'description' => 'Pantai Kuta Lombok terkenal dengan pasir putihnya yang memukau dan ombak yang cocok untuk surfing. Pemandangan sunset di sini merupakan salah satu yang terbaik di Indonesia.',
                'address' => 'Kuta, Lombok Tengah, Nusa Tenggara Barat',
                'latitude' => -8.9023,
                'longitude' => 116.2870,
                'open_hour' => '06:00',
                'close_hour' => '18:00',
                'ticket_price' => 15000.00,
                'phone' => '0370123456',
                'status' => 'published',
            ],
            [
                'destination_category_id' => 1, // Pantai
                'name' => 'Pantai Pink Lombok',
                'slug' => 'pantai-pink-lombok',
                'description' => 'Salah satu dari tujuh pantai berpasir pink di dunia. Perpaduan pasir merah muda dengan air laut biru jernih menciptakan pemandangan yang sangat eksotis.',
                'address' => 'Desa Tangsi, Jerowaru, Lombok Timur',
                'latitude' => -8.8500,
                'longitude' => 116.5500,
                'open_hour' => '07:00',
                'close_hour' => '17:00',
                'ticket_price' => 25000.00,
                'phone' => '0370654321',
                'status' => 'published',
            ],
            [
                'destination_category_id' => 2, // Pegunungan
                'name' => 'Rinjani Mountain',
                'slug' => 'gunung-rinjani',
                'description' => 'Gunung berapi aktif setinggi 3.726 meter ini merupakan gunung tertinggi kedua di Indonesia. Mendaki ke puncaknya dan melihat Danau Segara Anak adalah pengalaman tak terlupakan.',
                'address' => 'Sembalun, Lombok Timur, NTB',
                'latitude' => -8.4117,
                'longitude' => 116.4575,
                'open_hour' => '05:00',
                'close_hour' => '17:00',
                'ticket_price' => 150000.00,
                'phone' => '0370789101',
                'status' => 'published',
            ],
            [
                'destination_category_id' => 3, // Budaya
                'name' => 'Desa Sade Lombok',
                'slug' => 'desa-sade-lombok',
                'description' => 'Desa adat Sasak yang masih mempertahankan budaya dan arsitektur tradisional. Rumah-rumahnya beratap ilalang dan lantai tanah liat, menunjukkan kearifan lokal suku Sasak.',
                'address' => 'Rembitan, Pujut, Lombok Tengah',
                'latitude' => -8.8600,
                'longitude' => 116.2800,
                'open_hour' => '08:00',
                'close_hour' => '17:00',
                'ticket_price' => 10000.00,
                'phone' => '0370111222',
                'status' => 'published',
            ],
            [
                'destination_category_id' => 1, // Pantai
                'name' => 'Pantai Tanjung Aan',
                'slug' => 'pantai-tanjung-aan',
                'description' => 'Dikenal dengan butiran pasirnya yang seperti merica (nyiur), pantai ini memiliki dua teluk dengan karakteristik air laut yang berbeda, tenang di satu sisi dan berombak di sisi lain.',
                'address' => 'Tanjung Aan, Lombok Tengah, NTB',
                'latitude' => -8.9100,
                'longitude' => 116.3000,
                'open_hour' => '06:00',
                'close_hour' => '18:00',
                'ticket_price' => 10000.00,
                'phone' => '0370333444',
                'status' => 'published',
            ]
        ];

        foreach ($destinations as $dest) {
            $dest['manager_id'] = null; // Dibiarkan null saja untuk data dummy
            $dest['created_at'] = now();
            $dest['updated_at'] = now();
            DB::table('destinations')->insert($dest);
        }

        // Attach fasilitas ke destinasi (random)
        $destinationIds = DB::table('destinations')->pluck('id');
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
    }
}