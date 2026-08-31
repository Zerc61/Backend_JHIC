<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('⭐ Membuat review (3-5 bintang) untuk UMKM & hotel...');
        mt_srand(20260417);

        $now = now();

        $touristIds = DB::table('users')
            ->where('role', 'tourist')
            ->pluck('id')
            ->toArray();

        if (empty($touristIds)) {
            $this->command->warn('  ⚠️ Belum ada user tourist. Jalankan UserSeeder dulu.');
            return;
        }

        $hotelIds = DB::table('hotels')->pluck('id')->toArray();
        $umkmIds = DB::table('umkms')->pluck('id')->toArray();
        $destIds = DB::table('destinations')->pluck('id')->toArray();

        $comments = [
            'Puas banget, tempatnya bersih dan pelayanannya ramah. Pasti balik lagi!',
            'Keren, pengalaman yang sangat menyenangkan. Harga sesuai dengan kualitas.',
            'Mantap! Suasana nyaman, fasilitas lengkap, dan lokasi strategis.',
            'Lumayan oke, cocok untuk liburan bareng keluarga dan teman.',
            'Rekomendasi banget. Kecewa nggak akan, dijamin seru!',
            'Tempatnya bagus, cuma waktu weekend lumayan ramai. Worth it sih.',
            'Bintang lima! Servisnya cepat, barangnya berkualitas, harganya bersahabat.',
            'Nyaman dan bersih, pemilik atau stafnya sangat membantu kami.',
            'Menarik dan unik. Ada banyak spot foto yang instagramable.',
            'Sangat direkomendasikan untuk teman dan kerabat. Pengalaman tak terlupakan!',
            'Harga wajar, tempat nyaman, dan vibe-nya bikin betah berlama-lama.',
            'Pas buat healing. Udaranya sejuk, pemandangannya bikin adem.',
        ];

        $created = 0;

        // --- Reviews untuk destinasi ---
        foreach ($destIds as $destId) {
            $this->seedFor('App\\Models\\Destination', $destId, $touristIds, $comments, $now, $created);
        }

        // --- Reviews untuk hotel ---
        foreach ($hotelIds as $hotelId) {
            $this->seedFor('App\\Models\\Hotel', $hotelId, $touristIds, $comments, $now, $created);
        }

        // --- Reviews untuk UMKM ---
        foreach ($umkmIds as $umkmId) {
            $this->seedFor('App\\Models\\Umkm', $umkmId, $touristIds, $comments, $now, $created);
        }

        $this->command->info("  ✅ {$created} review baru");
    }

    private function seedFor(string $type, int $id, array $touristIds, array $comments, $now, int &$created): void
    {
        $count = rand(3, 5);
        $picked = (array) array_rand(array_flip($touristIds), min($count, count($touristIds)));

        foreach ($picked as $userId) {
            $inserted = DB::table('reviews')->insertOrIgnore([
                'user_id'         => (int) $userId,
                'reviewable_type' => $type,
                'reviewable_id'   => $id,
                'rating'          => rand(3, 5),
                'comment'         => $comments[array_rand($comments)],
                'helpful_count'   => rand(0, 15),
                'created_at'      => $now->copy()->subDays(rand(0, 60)),
                'updated_at'      => $now,
            ]);

            $created += $inserted ? 1 : 0;
        }
    }
}