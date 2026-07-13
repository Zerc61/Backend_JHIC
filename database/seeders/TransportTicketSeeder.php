<?php
// database/seeders/TransportTicketSeeder.php

namespace Database\Seeders;

use App\Models\TransportTicket;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TransportTicketSeeder extends Seeder
{
    public function run(): void
    {
        TransportTicket::query()->delete();

        // =============================================
        // 1. TIKET RANDOM (150 tiket, rute & tanggal acak)
        // =============================================
        TransportTicket::factory()->count(150)->create();

        // =============================================
        // 2. TIKET GUARANTEE (rute populer, pasti ada setiap 5 hari)
        //    Ini yang bikin halaman pencarian tidak kosong
        // =============================================
        $popularRoutes = [
            // Pesawat
            ['provider' => 'Lion Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'JT-892'],
            ['provider' => 'Garuda Indonesia', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'GA-401'],
            ['provider' => 'Citilink', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'QG-155'],
            ['provider' => 'Wings Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'IW-1872'],
            ['provider' => 'Lion Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'DPS', 'destination_name' => 'Ngurah Rai, Bali', 'flight' => 'JT-901'],
            ['provider' => 'Garuda Indonesia', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'DPS', 'destination_name' => 'Ngurah Rai, Bali', 'flight' => 'GA-201'],
            ['provider' => 'Batik Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'DPS', 'destination_name' => 'Ngurah Rai, Bali', 'flight' => 'ID-605'],
            ['provider' => 'Wings Air', 'mode' => 'pesawat', 'origin_code' => 'DPS', 'origin_name' => 'Ngurah Rai, Bali', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'IW-1890'],
            ['provider' => 'Lion Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'SUB', 'destination_name' => 'Juanda, Surabaya', 'flight' => 'JT-790'],
            ['provider' => 'Garuda Indonesia', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'SUB', 'destination_name' => 'Juanda, Surabaya', 'flight' => 'GA-305'],
            // Kereta
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'SBY', 'destination_name' => 'Stasiun Surabaya Gubeng', 'flight' => null],
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'YK', 'destination_name' => 'Stasiun Yogyakarta', 'flight' => null],
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'BD', 'destination_name' => 'Stasiun Bandung', 'flight' => null],
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'SBY', 'origin_name' => 'Stasiun Surabaya Gubeng', 'destination_code' => 'YK', 'destination_name' => 'Stasiun Yogyakarta', 'flight' => null],
            // Bus
            ['provider' => 'Lombok Trans', 'mode' => 'bus', 'origin_code' => 'SBY', 'origin_name' => 'Terminal Bungurasih, Surabaya', 'destination_code' => 'MBL', 'destination_name' => 'Terminal Mandalika, Lombok', 'flight' => null],
            ['provider' => 'Pahala Kencana', 'mode' => 'bus', 'origin_code' => 'SBY', 'origin_name' => 'Terminal Bungurasih, Surabaya', 'destination_code' => 'DPS', 'destination_name' => 'Terminal Mengwi, Bali', 'flight' => null],
            ['provider' => 'Rosalia Indah', 'mode' => 'bus', 'origin_code' => 'JKT', 'origin_name' => 'Terminal Pulo Gebang, Jakarta', 'destination_code' => 'SBY', 'destination_name' => 'Terminal Bungurasih, Surabaya', 'flight' => null],
            // Kapal
            ['provider' => 'Pelni', 'mode' => 'kapal', 'origin_code' => 'PDG', 'origin_name' => 'Pelabuhan Teluk Bayur, Padang', 'destination_code' => 'TJL', 'destination_name' => 'Pelabuhan Tanjung Luar, Lombok', 'flight' => null],
            ['provider' => 'Pelni', 'mode' => 'kapal', 'origin_code' => 'SBY', 'origin_name' => 'Pelabuhan Tanjung Perak, Surabaya', 'destination_code' => 'MKS', 'destination_name' => 'Pelabuhan Soekarno-Hatta, Makassar', 'flight' => null],
        ];

        // Jam keberangkatan per mode
        $hours = [
            'pesawat' => [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20],
            'kereta'  => [5, 7, 9, 11, 13, 15, 17, 19, 21, 23],
            'bus'     => [6, 8, 10, 12, 14, 16, 18, 20],
            'kapal'   => [16, 17, 18],
        ];

        $guaranteeCount = 0;

        // Buat tiket setiap 3 hari, untuk 60 hari ke depan
        for ($day = 3; $day <= 60; $day += 3) {
            $date = Carbon::today()->addDays($day);

            foreach ($popularRoutes as $route) {
                // Setiap rute dapat 1-2 jadwal per tanggal
                $schedulesPerRoute = $route['mode'] === 'pesawat' ? 2 : 1;

                for ($s = 0; $s < $schedulesPerRoute; $s++) {
                    $hourOptions = $hours[$route['mode']];
                    $hour = $hourOptions[($s * 3 + array_search($route['origin_code'], array_column($popularRoutes, 'origin_code'))) % count($hourOptions)];
                    $minute = $route['mode'] === 'kereta' ? 0 : ($s % 2 === 0 ? 0 : 30);

                    $departure = $date->copy()->setHour($hour)->setMinute($minute)->setSecond(0);

                    $durations = [
                        'pesawat' => 150,
                        'kereta'  => 480,
                        'bus'     => 720,
                        'kapal'   => 960,
                    ];
                    $duration = $durations[$route['mode']] + ($s * 30);
                    $arrival = $departure->copy()->addMinutes($duration);

                    $prices = [
                        'pesawat' => [650000, 750000, 850000, 950000, 1250000],
                        'kereta'  => [350000, 400000, 450000],
                        'bus'     => [250000, 280000, 320000],
                        'kapal'   => [300000, 350000, 400000],
                    ];
                    $price = $prices[$route['mode']][$s % count($prices[$route['mode']])];

                    TransportTicket::create([
                        'provider'         => $route['provider'],
                        'transport_mode'   => $route['mode'],
                        'origin_code'      => $route['origin_code'],
                        'origin_name'      => $route['origin_name'],
                        'destination_code' => $route['destination_code'],
                        'destination_name' => $route['destination_name'],
                        'flight_number'    => $route['flight'],
                        'departure_time'   => $departure,
                        'arrival_time'     => $arrival,
                        'duration_minutes' => $duration,
                        'is_transit'       => false,
                        'transit_info'     => null,
                        'class_type'       => $route['mode'] === 'pesawat' ? 'Ekonomi' : ($route['mode'] === 'kereta' ? 'Eksekutif' : ($route['mode'] === 'kapal' ? 'Kelas I' : 'Eksekutif')),
                        'available_seats'  => rand(8, 60),
                        'price_per_ticket' => $price,
                        'status'           => 'available',
                        'valid_until'      => $departure->copy()->subHour(),
                    ]);

                    $guaranteeCount++;
                }
            }
        }

        // =============================================
        // SUMMARY
        // =============================================
        $total = TransportTicket::count();
        $pesawat = TransportTicket::where('transport_mode', 'pesawat')->count();
        $kereta  = TransportTicket::where('transport_mode', 'kereta')->count();
        $bus     = TransportTicket::where('transport_mode', 'bus')->count();
        $kapal   = TransportTicket::where('transport_mode', 'kapal')->count();

        $this->command->info("✅ {$total} tiket transport berhasil di-generate! (150 random + {$guaranteeCount} guarantee)");

        $this->command->table(
            ['Mode', 'Jumlah'],
            [
                ['Pesawat', $pesawat],
                ['Kereta', $kereta],
                ['Bus', $bus],
                ['Kapal', $kapal],
                ['---', '---'],
                ['Total', $total],
            ]
        );
    }
}