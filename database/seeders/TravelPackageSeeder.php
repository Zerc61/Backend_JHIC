<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TravelPackageSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📦 Membuat 50+ paket wisata...');
        mt_srand(20260417);

        $now = now();
        $managerId = DB::table('users')->where('role', 'manager')->first()?->id ?? 1;
        $destinations = DB::table('destinations')
            ->whereNotIn('slug', ['pantai-kuta-lombok', 'pantai-pink-lombok', 'gunung-rinjani', 'desa-sade-lombok', 'pantai-tanjung-aan'])
            ->select('id', 'name', 'slug')
            ->get();
        $hotels = DB::table('hotels')->select('id', 'name', 'destination_id')->get();

        if ($destinations->isEmpty()) {
            $this->command->warn('  ⚠️ Belum ada destinasi. Jalankan DestinationSeeder dulu.');
            return;
        }

        $hotelsByDest = $hotels->groupBy('destination_id');

        $thumb = 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=800&q=80';
        $thumb2 = 'https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=800&q=80';
        $thumb3 = 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80';
        $thumbs = [$thumb, $thumb2, $thumb3];

        $durations = [
            ['days' => 1,  'nights' => 0, 'label' => '1D'],
            ['days' => 1,  'nights' => 1, 'label' => '1H1M'],
            ['days' => 2,  'nights' => 1, 'label' => '2H1M'],
            ['days' => 2,  'nights' => 2, 'label' => '2H2M'],
            ['days' => 3,  'nights' => 2, 'label' => '3H2M'],
            ['days' => 3,  'nights' => 3, 'label' => '3H3M'],
            ['days' => 4,  'nights' => 3, 'label' => '4H3M'],
            ['days' => 5,  'nights' => 4, 'label' => '5H4M'],
        ];

        $themes = [
            'Adventure', 'Budget', 'Premium', 'Family', 'Honeymoon',
            'Backpacker', 'Cultural', 'Nature', 'Beach', 'Mountain',
            'Explorer', 'Photography', 'Wellness', 'Eco', 'Heritage',
        ];

        $created = 0;
        $destArray = $destinations->values()->toArray();

        for ($i = 0; $i < 55; $i++) {
            $dest = $destArray[$i % count($destArray)];
            $dur = $durations[$i % count($durations)];
            $theme = $themes[$i % count($themes)];
            $name = "{$theme} {$dest->name} {$dur['label']}";
            $slug = Str::slug($name) . '-' . ($i + 1);

            if (DB::table('travel_packages')->where('slug', $slug)->exists()) continue;

            $basePrice = match (true) {
                $dur['days'] >= 5 => 3000000 + ($i * 80000),
                $dur['days'] >= 3 => 1500000 + ($i * 60000),
                $dur['days'] >= 2 => 800000 + ($i * 40000),
                default           => 250000 + ($i * 30000),
            };

            // Hotel se-destinasi agar koheren
            $sameDest = $hotelsByDest->get($dest->id);
            $hotel = $sameDest?->isNotEmpty() ? $sameDest->get($i % $sameDest->count()) : null;
            $included = ['Transportasi + Supir', "Tiket {$dest->name}", 'Makan siang ' . $dur['days'] . 'x'];
            if ($hotel) $included[] = "Hotel {$hotel->name} {$dur['nights']} malam";

            $pid = DB::table('travel_packages')->insertGetId([
                'manager_id'        => $managerId,
                'destination_id'    => $dest->id,
                'hotel_id'          => $hotel?->id,
                'name'              => $name,
                'slug'              => $slug,
                'description'       => "Paket wisata {$theme} ke {$dest->name} selama {$dur['label']}. Termasuk transport, tiket masuk, akomodasi, dan makan. Liburan tanpa repot.",
                'thumbnail'         => $thumbs[$i % 3],
                'duration_days'     => $dur['days'],
                'duration_nights'   => $dur['nights'],
                'price_per_person'  => $basePrice,
                'included_items'    => json_encode($included),
                'excluded_items'    => json_encode(['Pengeluaran pribadi', 'Tip pemandu']),
                'meals_included'    => json_encode(['lunch' => $dur['days']]),
                'terms_conditions'  => 'Pembatalan H-7: refund 50%. H-3: tidak ada refund.',
                'status'            => 'published',
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            // Gallery
            foreach (array_slice($thumbs, 0, 2) as $gi => $gimg) {
                DB::table('travel_package_galleries')->insert([
                    'travel_package_id' => $pid,
                    'image'             => $gimg,
                    'caption'           => "{$name} — Foto " . ($gi + 1),
                    'sort_order'        => $gi,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }

            // 2-3 schedules (deterministik)
            $scheduleCount = 2 + ($i % 2);
            for ($s = 0; $s < $scheduleCount; $s++) {
                $dep = $now->copy()->addDays(12 + (($i * 3 + $s * 5) % 55));
                DB::table('travel_package_schedules')->insertOrIgnore([
                    'travel_package_id' => $pid,
                    'departure_date'    => $dep->format('Y-m-d'),
                    'return_date'       => $dep->copy()->addDays($dur['days'] - 1)->format('Y-m-d'),
                    'max_capacity'      => 10 + (($i + $s) % 16),
                    'current_booked'    => 0,
                    'status'            => 'available',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }

            $created++;
        }

        $this->command->info("  ✅ {$created} paket wisata baru + schedules + galleries");
    }
}
