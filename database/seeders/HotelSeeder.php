<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HotelSeeder extends Seeder
{
    private array $oldSlugs = [
        'pantai-kuta-lombok', 'pantai-pink-lombok', 'gunung-rinjani',
        'desa-sade-lombok', 'pantai-tanjung-aan',
    ];

    private array $regionDest = [
        'Surabaya'   => 'tugu-pahlawan',
        'Malang'     => 'pantai-balekambang',
        'Batu'       => 'taman-selecta',
        'Banyuwangi' => 'gunung-ijen',
        'Kediri'     => 'candi-penataran',
        'Blitar'     => 'makam-bung-karno',
        'Tulungagung'=> 'pantai-popoh',
        'Pacitan'    => 'pantai-klayar',
        'Madiun'     => 'alun-alun-madiun',
        'Probolinggo'=> 'gunung-bromo',
        'Lumajang'   => 'gunung-bromo',
        'Jember'     => 'kawah-ijen-blue-fire',
        'Bondowoso'  => 'kawah-ijen-blue-fire',
        'Situbondo'  => 'taman-nasional-baluran',
        'Pamekasan'  => 'makam-sunan-giri',
        'Sampang'    => 'makam-sunan-giri',
        'Gresik'     => 'makam-sunan-giri',
        'Sidoarjo'   => 'lumpur-lapindo',
        'Mojokerto'  => 'candi-tikus',
        'Lamongan'   => 'soto-lamongan',
    ];

    private array $suffixes = [
        'Malang', 'Batu', 'Surabaya', 'Banyuwangi', 'Kediri', 'Blitar', 'Tulungagung',
        'Pacitan', 'Madiun', 'Probolinggo', 'Lumajang', 'Jember', 'Bondowoso',
        'Situbondo', 'Pamekasan', 'Sampang', 'Gresik', 'Sidoarjo', 'Mojokerto', 'Lamongan',
    ];

    public function run(): void
    {
        $this->command->info('🏨 Membuat 50+ hotel (koheren dengan wilayah)...');
        mt_srand(20260417);

        $now = now();
        $managerId = DB::table('users')->where('role', 'manager')->first()?->id ?? 1;

        $destRows = DB::table('destinations')->whereNotIn('slug', $this->oldSlugs)->get(['id', 'slug']);
        $destIdBySlug = [];
        foreach ($destRows as $d) $destIdBySlug[$d->slug] = $d->id;

        if (empty($destIdBySlug)) {
            $this->command->warn('  ⚠️ Belum ada destinasi. Jalankan DestinationSeeder dulu.');
            return;
        }

        $thumb = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80';
        $room1 = 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80';
        $room2 = 'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=800&q=80';
        $room3 = 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&q=80';

        $hotelTypes = ['Resort', 'Hotel', 'Villa', 'Homestay', 'Lodge', 'Inn', 'Cottage', 'Bungalow'];
        $themes = [
            'Beachfront', 'Mountain View', 'City Center', 'Garden', 'Lake View',
            'Riverside', 'Hillside', 'Valley', 'Pool', 'Tropical',
        ];
        $starRates = [2, 3, 3, 3, 4, 4, 5];

        $created = 0;

        for ($i = 0; $i < 55; $i++) {
            $suffix = $this->suffixes[$i % count($this->suffixes)];
            $destSlug = $this->regionDest[$suffix] ?? 'pantai-sendang-biru';
            $destId = $destIdBySlug[$destSlug] ?? null;

            $name = $themes[$i % count($themes)] . ' ' . $hotelTypes[$i % count($hotelTypes)] . ' ' . $suffix;
            $slug = Str::slug($name);

            if (DB::table('hotels')->where('slug', $slug)->exists()) continue;

            $stars = $starRates[$i % count($starRates)];
            $basePrice = match (true) {
                $stars >= 5 => 1500000 + ($i * 100000),
                $stars >= 4 => 500000 + ($i * 50000),
                $stars >= 3 => 200000 + ($i * 25000),
                default     => 80000 + ($i * 12000),
            };

            $hid = DB::table('hotels')->insertGetId([
                'manager_id'     => $managerId,
                'destination_id' => $destId,
                'name'           => $name,
                'slug'           => $slug,
                'description'    => "{$name} menawarkan pengalaman menginap terbaik di {$suffix}, Jawa Timur dengan fasilitas modern dan pelayanan prima. Cocok untuk wisatawan solo, keluarga, maupun rombongan.",
                'address'        => 'Jl. Wisata No. ' . ($i + 1) . ', ' . $suffix . ', Jawa Timur',
                'latitude'       => -7.4 + ($i * 0.002),
                'longitude'      => 112.0 + ($i * 0.003),
                'star_rating'    => $stars,
                'phone'          => '0341-' . (100000 + $i * 137),
                'website'        => null,
                'check_in_time'  => '14:00:00',
                'check_out_time' => '12:00:00',
                'thumbnail'      => $thumb,
                'status'         => 'published',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            foreach ([$thumb, $room1, $room2, $room3] as $i2 => $gimg) {
                DB::table('hotel_galleries')->insert([
                    'hotel_id'   => $hid,
                    'image'      => $gimg,
                    'caption'    => "{$name} — Foto " . ($i2 + 1),
                    'sort_order' => $i2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $roomCount = 2 + ($i % 3);
            $roomNames = ['Standard', 'Deluxe', 'Suite', 'Family', 'Superior', 'Premium'];
            for ($r = 0; $r < $roomCount; $r++) {
                $rname = $roomNames[$r % count($roomNames)] . ' Room';
                $rprice = $basePrice + ($r * (int) ($basePrice * 0.3));
                DB::table('hotel_rooms')->insert([
                    'hotel_id'        => $hid,
                    'name'            => $rname,
                    'description'     => "{$rname} di {$name}. Bersih, nyaman, dan modern.",
                    'capacity'        => $r === 3 ? 4 : 2,
                    'price_per_night' => $rprice,
                    'total_rooms'     => 3 + ($i % 12),
                    'amenities'       => json_encode(['AC', 'WiFi', 'TV', 'Kamar Mandi Dalam']),
                    'status'          => 'available',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }

            $created++;
        }

        $this->command->info("  ✅ {$created} hotel baru + rooms + galleries");
    }
}