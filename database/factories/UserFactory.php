<?php

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
        // 1. TIKET RANDOM (150 tiket, tersebar 365 hari)
        // =============================================
        $this->command->info('Generating 150 random tickets...');
        TransportTicket::factory()->count(150)->create();

        // =============================================
        // 2. TIKET GUARANTEE (rute populer, teratur 365 hari)
        // =============================================
        $this->command->info('Generating guarantee tickets (365 days)...');

        $popularRoutes = [
            // ── SUPER POPULER: setiap hari, 2 jadwal ──
            ['provider' => 'Lion Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'JT-892', 'class' => 'Ekonomi', 'price' => 850000, 'duration' => 150, 'freq' => 1, 'hours' => [7, 14]],
            ['provider' => 'Garuda Indonesia', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'GA-401', 'class' => 'Bisnis', 'price' => 1250000, 'duration' => 145, 'freq' => 1, 'hours' => [9, 16]],
            ['provider' => 'Wings Air', 'mode' => 'pesawat', 'origin_code' => 'DPS', 'origin_name' => 'Ngurah Rai, Bali', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'IW-1890', 'class' => 'Ekonomi', 'price' => 650000, 'duration' => 45, 'freq' => 1, 'hours' => [8, 15]],

            // ── POPULER PESAWAT: setiap 2 hari, 1-2 jadwal ──
            ['provider' => 'Citilink', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'QG-155', 'class' => 'Ekonomi', 'price' => 750000, 'duration' => 155, 'freq' => 2, 'hours' => [11]],
            ['provider' => 'Lion Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'DPS', 'destination_name' => 'Ngurah Rai, Bali', 'flight' => 'JT-901', 'class' => 'Ekonomi', 'price' => 950000, 'duration' => 140, 'freq' => 2, 'hours' => [6, 13]],
            ['provider' => 'Garuda Indonesia', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'DPS', 'destination_name' => 'Ngurah Rai, Bali', 'flight' => 'GA-201', 'class' => 'Bisnis', 'price' => 1350000, 'duration' => 135, 'freq' => 2, 'hours' => [10]],
            ['provider' => 'Batik Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'DPS', 'destination_name' => 'Ngurah Rai, Bali', 'flight' => 'ID-605', 'class' => 'Ekonomi', 'price' => 880000, 'duration' => 145, 'freq' => 2, 'hours' => [17]],
            ['provider' => 'Lion Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'SUB', 'destination_name' => 'Juanda, Surabaya', 'flight' => 'JT-790', 'class' => 'Ekonomi', 'price' => 650000, 'duration' => 90, 'freq' => 2, 'hours' => [8, 15]],
            ['provider' => 'Garuda Indonesia', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'SUB', 'destination_name' => 'Juanda, Surabaya', 'flight' => 'GA-305', 'class' => 'Ekonomi', 'price' => 780000, 'duration' => 85, 'freq' => 2, 'hours' => [11]],
            ['provider' => 'Sriwijaya Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'UPG', 'destination_name' => 'Sultan Hasanuddin, Makassar', 'flight' => 'SJ-612', 'class' => 'Ekonomi', 'price' => 1100000, 'duration' => 160, 'freq' => 2, 'hours' => [7]],
            ['provider' => 'Citilink', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'BPN', 'destination_name' => 'Sultan Aji M. Sulaiman, Balikpapan', 'flight' => 'QG-780', 'class' => 'Ekonomi', 'price' => 1050000, 'duration' => 150, 'freq' => 2, 'hours' => [9]],
            ['provider' => 'Lion Air', 'mode' => 'pesawat', 'origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'KNO', 'destination_name' => 'Kualanamu, Medan', 'flight' => 'JT-395', 'class' => 'Ekonomi', 'price' => 900000, 'duration' => 150, 'freq' => 2, 'hours' => [6]],
            ['provider' => 'Wings Air', 'mode' => 'pesawat', 'origin_code' => 'SUB', 'origin_name' => 'Juanda, Surabaya', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International', 'flight' => 'IW-1872', 'class' => 'Ekonomi', 'price' => 550000, 'duration' => 75, 'freq' => 2, 'hours' => [10]],

            // ── KERETA: setiap 3 hari ──
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'SBY', 'destination_name' => 'Stasiun Surabaya Gubeng', 'flight' => null, 'class' => 'Eksekutif', 'price' => 450000, 'duration' => 480, 'freq' => 3, 'hours' => [8, 20]],
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'YK', 'destination_name' => 'Stasiun Yogyakarta', 'flight' => null, 'class' => 'Eksekutif', 'price' => 380000, 'duration' => 420, 'freq' => 3, 'hours' => [7, 19]],
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'BD', 'destination_name' => 'Stasiun Bandung', 'flight' => null, 'class' => 'Bisnis', 'price' => 150000, 'duration' => 180, 'freq' => 3, 'hours' => [6, 12, 18]],
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'SM', 'destination_name' => 'Stasiun Semarang Tawang', 'flight' => null, 'class' => 'Eksekutif', 'price' => 300000, 'duration' => 360, 'freq' => 3, 'hours' => [9]],
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'SBY', 'origin_name' => 'Stasiun Surabaya Gubeng', 'destination_code' => 'YK', 'destination_name' => 'Stasiun Yogyakarta', 'flight' => null, 'class' => 'Ekonomi', 'price' => 80000, 'duration' => 300, 'freq' => 3, 'hours' => [7, 14]],
            ['provider' => 'KAI', 'mode' => 'kereta', 'origin_code' => 'SBY', 'origin_name' => 'Stasiun Surabaya Gubeng', 'destination_code' => 'ML', 'destination_name' => 'Stasiun Malang', 'flight' => null, 'class' => 'Ekonomi', 'price' => 50000, 'duration' => 120, 'freq' => 3, 'hours' => [6, 16]],

            // ── BUS: setiap 5 hari ──
            ['provider' => 'Lombok Trans', 'mode' => 'bus', 'origin_code' => 'SBY', 'origin_name' => 'Terminal Bungurasih, Surabaya', 'destination_code' => 'MBL', 'destination_name' => 'Terminal Mandalika, Lombok', 'flight' => null, 'class' => 'Eksekutif', 'price' => 320000, 'duration' => 720, 'freq' => 5, 'hours' => [17]],
            ['provider' => 'Pahala Kencana', 'mode' => 'bus', 'origin_code' => 'SBY', 'origin_name' => 'Terminal Bungurasih, Surabaya', 'destination_code' => 'DPS', 'destination_name' => 'Terminal Mengwi, Bali', 'flight' => null, 'class' => 'Eksekutif', 'price' => 280000, 'duration' => 480, 'freq' => 5, 'hours' => [8, 20]],
            ['provider' => 'Rosalia Indah', 'mode' => 'bus', 'origin_code' => 'JKT', 'origin_name' => 'Terminal Pulo Gebang, Jakarta', 'destination_code' => 'SBY', 'destination_name' => 'Terminal Bungurasih, Surabaya', 'flight' => null, 'class' => 'Eksekutif', 'price' => 380000, 'duration' => 840, 'freq' => 5, 'hours' => [16]],
            ['provider' => 'Sinar Jaya', 'mode' => 'bus', 'origin_code' => 'JKT', 'origin_name' => 'Terminal Pulo Gebang, Jakarta', 'destination_code' => 'BD', 'destination_name' => 'Terminal Leuwi Panjang, Bandung', 'flight' => null, 'class' => 'Ekonomi', 'price' => 120000, 'duration' => 180, 'freq' => 5, 'hours' => [6, 12, 18]],
            ['provider' => 'Lorena', 'mode' => 'bus', 'origin_code' => 'BD', 'origin_name' => 'Terminal Leuwi Panjang, Bandung', 'destination_code' => 'SBY', 'destination_name' => 'Terminal Bungurasih, Surabaya', 'flight' => null, 'class' => 'Eksekutif', 'price' => 300000, 'duration' => 600, 'freq' => 5, 'hours' => [19]],
            ['provider' => 'Garuda Mas', 'mode' => 'bus', 'origin_code' => 'YK', 'origin_name' => 'Terminal Giwangan, Yogyakarta', 'destination_code' => 'SBY', 'destination_name' => 'Terminal Bungurasih, Surabaya', 'flight' => null, 'class' => 'Ekonomi', 'price' => 150000, 'duration' => 360, 'freq' => 5, 'hours' => [7, 15]],
            ['provider' => 'Lombok Trans', 'mode' => 'bus', 'origin_code' => 'MBL', 'origin_name' => 'Terminal Mandalika, Lombok', 'destination_code' => 'DPS', 'destination_name' => 'Terminal Mengwi, Bali', 'flight' => null, 'class' => 'Eksekutif', 'price' => 250000, 'duration' => 360, 'freq' => 5, 'hours' => [8]],

            // ── KAPAL: setiap 7 hari ──
            ['provider' => 'Pelni', 'mode' => 'kapal', 'origin_code' => 'PDG', 'origin_name' => 'Pelabuhan Teluk Bayur, Padang', 'destination_code' => 'TJL', 'destination_name' => 'Pelabuhan Tanjung Luar, Lombok', 'flight' => null, 'class' => 'Kelas I', 'price' => 400000, 'duration' => 960, 'freq' => 7, 'hours' => [17]],
            ['provider' => 'Pelni', 'mode' => 'kapal', 'origin_code' => 'SBY', 'origin_name' => 'Pelabuhan Tanjung Perak, Surabaya', 'destination_code' => 'MKS', 'destination_name' => 'Pelabuhan Soekarno-Hatta, Makassar', 'flight' => null, 'class' => 'Kelas II', 'price' => 350000, 'duration' => 1080, 'freq' => 7, 'hours' => [16]],
            ['provider' => 'Dharma Lautan Utama', 'mode' => 'kapal', 'origin_code' => 'SBY', 'origin_name' => 'Pelabuhan Tanjung Perak, Surabaya', 'destination_code' => 'AMB', 'destination_name' => 'Pelabuhan Yos Sudarso, Ambon', 'flight' => null, 'class' => 'Kelas III', 'price' => 300000, 'duration' => 1440, 'freq' => 7, 'hours' => [18]],
            ['provider' => 'Pelni', 'mode' => 'kapal', 'origin_code' => 'MKS', 'origin_name' => 'Pelabuhan Soekarno-Hatta, Makassar', 'destination_code' => 'TJL', 'destination_name' => 'Pelabuhan Tanjung Luar, Lombok', 'flight' => null, 'class' => 'Kelas II', 'price' => 380000, 'duration' => 840, 'freq' => 7, 'hours' => [16]],
            ['provider' => 'Djakarta Lloyd', 'mode' => 'kapal', 'origin_code' => 'JKT', 'origin_name' => 'Pelabuhan Tanjung Priok, Jakarta', 'destination_code' => 'SBY', 'destination_name' => 'Pelabuhan Tanjung Perak, Surabaya', 'flight' => null, 'class' => 'Kelas I', 'price' => 450000, 'duration' => 1200, 'freq' => 7, 'hours' => [17]],
            ['provider' => 'Ferry Express', 'mode' => 'kapal', 'origin_code' => 'BAT', 'origin_name' => 'Pelabuhan Sekupang, Batam', 'destination_code' => 'SIN', 'destination_name' => 'Pelabuhan Harbour Front, Singapura', 'flight' => null, 'class' => 'Kelas I', 'price' => 500000, 'duration' => 120, 'freq' => 7, 'hours' => [8, 14]],
        ];

        // =============================================
        // LOOP 365 HARI
        // =============================================
        $guaranteeCount = 0;
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addYear();
        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dayIndex = $startDate->diffInDays($date) + 1; // 1, 2, 3, ... 365

            foreach ($popularRoutes as $route) {
                // Cek frekuensi: freq=1 → tiap hari, freq=2 → tiap 2 hari, dst
                if ($dayIndex % $route['freq'] !== 0) {
                    continue;
                }

                foreach ($route['hours'] as $hour) {
                    // Menit: kereta selalu 0, lainnya bergantian 0/30
                    $minute = $route['mode'] === 'kereta' ? 0 : ($hour % 2 === 0 ? 0 : 30);

                    // Sedikit variasi harga berdasarkan hari (weekend lebih mahal)
                    $isWeekend = in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
                    $priceMultiplier = $isWeekend ? 1.15 : 1.0;
                    $finalPrice = (int) ($route['price'] * $priceMultiplier);

                    // Sedikit variasi seat
                    $seats = rand(8, 60);

                    // ✅ PAKAI FACTORY + STATE
                    TransportTicket::factory()
                        ->scheduledOn($date, $hour, $minute, $route['duration'])
                        ->create([
                            'provider'         => $route['provider'],
                            'transport_mode'   => $route['mode'],
                            'origin_code'      => $route['origin_code'],
                            'origin_name'      => $route['origin_name'],
                            'destination_code' => $route['destination_code'],
                            'destination_name' => $route['destination_name'],
                            'flight_number'    => $route['flight'],
                            'is_transit'       => false,
                            'transit_info'     => null,
                            'class_type'       => $route['class'],
                            'available_seats'  => $seats,
                            'price_per_ticket' => $finalPrice,
                            'status'           => 'available',
                        ]);

                    $guaranteeCount++;
                }
            }

            // Progress bar setiap 30 hari
            if ($dayIndex % 30 === 0) {
                $this->command->info("  Day {$dayIndex}/365 - {$guaranteeCount} tickets generated so far...");
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

        $firstDate = TransportTicket::orderBy('departure_time')->first()?->departure_time?->format('Y-m-d');
        $lastDate  = TransportTicket::orderByDesc('departure_time')->first()?->departure_time?->format('Y-m-d');

        $this->command->newLine();
        $this->command->info("Done! {$total} tickets generated (150 random + {$guaranteeCount} guarantee)");
        $this->command->info("Date range: {$firstDate} -> {$lastDate}");
        $this->command->newLine();

        $this->command->table(
            ['Mode', 'Jumlah', 'Persentase'],
            [
                ['Pesawat', $pesawat, round($pesawat / $total * 100) . '%'],
                ['Kereta',  $kereta,  round($kereta / $total * 100) . '%'],
                ['Bus',     $bus,     round($bus / $total * 100) . '%'],
                ['Kapal',   $kapal,   round($kapal / $total * 100) . '%'],
                ['---', '---', '---'],
                ['Total', $total, '100%'],
            ]
        );
    }
}