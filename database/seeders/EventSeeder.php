<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎉 Membuat 15+ event...');
        mt_srand(20260417);

        $now = now();
        $adminId = DB::table('users')->where('role', 'admin')->first()?->id ?? 1;
        $destRows = DB::table('destinations')
            ->whereNotIn('slug', ['pantai-kuta-lombok', 'pantai-pink-lombok', 'gunung-rinjani', 'desa-sade-lombok', 'pantai-tanjung-aan'])
            ->get(['id', 'slug']);

        if ($destRows->isEmpty()) {
            $this->command->warn('  ⚠️ Belum ada destinasi. Jalankan DestinationSeeder dulu.');
            return;
        }

        $destSlugById = $destRows->pluck('slug', 'id')->all();

        // wilayah pada lokasi event → destinasi slug
        $regionKeywords = [
            'malang'     => 'pantai-balekambang',
            'batu'       => 'taman-selecta',
            'surabaya'   => 'tugu-pahlawan',
            'banyuwangi' => 'gunung-ijen',
            'bromo'      => 'gunung-bromo',
            'kediri'     => 'candi-penataran',
            'blitar'     => 'makam-bung-karno',
            'madura'     => 'makam-sunan-giri',
            'jember'     => 'kawah-ijen-blue-fire',
            'tulungagung' => 'pantai-popoh',
            'pacitan'    => 'pantai-klayar',
        ];

        $img = 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=800&q=80';

        $events = [
            ['title' => 'Festival Layang-Layang Malang',     'slug' => 'festival-layang-layang-malang',     'loc' => 'Alun-Alun Malang',         'status' => 'upcoming',   'days' => 15],
            ['title' => 'Banyuwangi Festival',               'slug' => 'banyuwangi-festival',               'loc' => 'Gandrung Budaya Banyuwangi','status' => 'upcoming',   'days' => 30],
            ['title' => 'Festival Musik Bromo',              'slug' => 'festival-musik-bromo',              'loc' => 'Savanna Bromo',            'status' => 'upcoming',   'days' => 45],
            ['title' => 'Pasar Malang Tempo Doeloe',         'slug' => 'pasar-malang-tempo-doeloe',         'loc' => 'Jl. Ijen Malang',          'status' => 'upcoming',   'days' => 20],
            ['title' => 'Surabaya Food Festival',            'slug' => 'surabaya-food-festival',            'loc' => 'Kenjeran Park Surabaya',   'status' => 'upcoming',   'days' => 25],
            ['title' => 'Bromo Marathon 2026',               'slug' => 'bromo-marathon-2026',               'loc' => 'Taman Nasional Bromo',     'status' => 'upcoming',   'days' => 35],
            ['title' => 'Kediri Coffee Festival',            'slug' => 'kediri-coffee-festival',            'loc' => 'Alun-Alun Kediri',         'status' => 'upcoming',   'days' => 18],
            ['title' => 'Malang Jazz Night',                 'slug' => 'malang-jazz-night',                 'loc' => 'BNS Malang',               'status' => 'upcoming',   'days' => 12],
            ['title' => 'Festival Kuliner Jatim',            'slug' => 'festival-kuliner-jatim',            'loc' => 'Taman Bung Karno Surabaya','status' => 'ongoing',    'days' => 0],
            ['title' => 'Batu Flower Festival',              'slug' => 'batu-flower-festival',              'loc' => 'Taman Selecta Batu',       'status' => 'ongoing',    'days' => -1],
            ['title' => 'Gebyar Seni Budaya Blitar',         'slug' => 'gebyar-seni-budaya-blitar',         'loc' => 'Makam Bung Karno Blitar',  'status' => 'finished',   'days' => -10],
            ['title' => 'Festival Karapan Sapi',             'slug' => 'festival-karapan-sapi',             'loc' => 'Madura',                   'status' => 'finished',   'days' => -15],
            ['title' => 'Ijen Ultra Trail Run',              'slug' => 'ijen-ultra-trail-run',              'loc' => 'Kawah Ijen Banyuwangi',    'status' => 'finished',   'days' => -20],
            ['title' => 'Jember Fashion Carnival',           'slug' => 'jember-fashion-carnival',           'loc' => 'Jember',                   'status' => 'finished',   'days' => -25],
            ['title' => 'Festival Tari Tradisional',         'slug' => 'festival-tari-tradisional',         'loc' => 'Candi Penataran Blitar',   'status' => 'upcoming',   'days' => 40],
            ['title' => 'Tulungagung Art Week',              'slug' => 'tulungagung-art-week',              'loc' => 'Alun-Alun Tulungagung',    'status' => 'upcoming',   'days' => 50],
            ['title' => 'Pacitan Surfing Competition',       'slug' => 'pacitan-surfing-competition',       'loc' => 'Pantai Klayar Pacitan',    'status' => 'upcoming',   'days' => 55],
        ];

        $created = 0;

        foreach ($events as $e) {
            if (DB::table('events')->where('slug', $e['slug'])->exists()) continue;

            $start = $now->copy()->addDays($e['days']);

            // destinasi dari kota event, fallback acak deterministik
            $destId = null;
            $lowerLoc = strtolower($e['loc']);
            foreach ($regionKeywords as $kw => $dSlug) {
                if (Str::contains($lowerLoc, $kw)) {
                    $found = $destRows->firstWhere('slug', $dSlug);
                    if ($found) { $destId = $found->id; break; }
                }
            }
            if (!$destId) $destId = $destRows->pluck('id')[$created % $destRows->count()];

            DB::table('events')->insertGetId([
                'destination_id' => $destId,
                'created_by'     => $adminId,
                'title'          => $e['title'],
                'slug'           => $e['slug'],
                'description'    => "{$e['title']} — event menarik di {$e['loc']}. Jangan lewatkan kesempatan untuk ikut serta dan nikmati pengalaman seru!",
                'start_date'     => $start,
                'end_date'       => $start->copy()->addHours(rand(6, 72)),
                'location'       => $e['loc'],
                'image'          => $img,
                'status'         => $e['status'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            $created++;
        }

        $this->command->info("  ✅ {$created} event baru");
    }
}
