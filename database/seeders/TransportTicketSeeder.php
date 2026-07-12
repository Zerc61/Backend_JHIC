<?php

// database/seeders/TransportTicketSeeder.php

namespace Database\Seeders;

use App\Models\TransportTicket;
use Illuminate\Database\Seeder;

class TransportTicketSeeder extends Seeder
{
    public function run(): void
    {
        $tickets = [
            // ✈️ Pesawat Jakarta → Lombok (Direct)
            [
                'provider'         => 'Lion Air',
                'transport_mode'   => 'pesawat',
                'origin_code'      => 'CGK',
                'origin_name'      => 'Jakarta Soekarno-Hatta',
                'destination_code' => 'LOP',
                'destination_name' => 'Lombok International',
                'flight_number'    => 'JT-892',
                'departure_time'   => '2026-08-15 10:00:00',
                'arrival_time'     => '2026-08-15 12:30:00',
                'duration_minutes' => 150,
                'is_transit'       => false,
                'transit_info'     => null,
                'class_type'       => 'Ekonomi',
                'available_seats'  => 23,
                'price_per_ticket' => 850000,
                'status'           => 'available',
                'valid_until'      => '2026-08-15 09:00:00',
            ],
            [
                'provider'         => 'Garuda Indonesia',
                'transport_mode'   => 'pesawat',
                'origin_code'      => 'CGK',
                'origin_name'      => 'Jakarta Soekarno-Hatta',
                'destination_code' => 'LOP',
                'destination_name' => 'Lombok International',
                'flight_number'    => 'GA-401',
                'departure_time'   => '2026-08-15 07:00:00',
                'arrival_time'     => '2026-08-15 09:30:00',
                'duration_minutes' => 150,
                'is_transit'       => false,
                'transit_info'     => null,
                'class_type'       => 'Ekonomi',
                'available_seats'  => 15,
                'price_per_ticket' => 1250000,
                'status'           => 'available',
                'valid_until'      => '2026-08-15 06:00:00',
            ],
            [
                'provider'         => 'Citilink',
                'transport_mode'   => 'pesawat',
                'origin_code'      => 'CGK',
                'origin_name'      => 'Jakarta Soekarno-Hatta',
                'destination_code' => 'LOP',
                'destination_name' => 'Lombok International',
                'flight_number'    => 'QG-155',
                'departure_time'   => '2026-08-15 14:00:00',
                'arrival_time'     => '2026-08-15 16:30:00',
                'duration_minutes' => 150,
                'is_transit'       => false,
                'transit_info'     => null,
                'class_type'       => 'Ekonomi',
                'available_seats'  => 40,
                'price_per_ticket' => 720000,
                'status'           => 'available',
                'valid_until'      => '2026-08-15 13:00:00',
            ],

            // ✈️ Pesawat Jakarta → Lombok (Transit)
            [
                'provider'         => 'Wings Air',
                'transport_mode'   => 'pesawat',
                'origin_code'      => 'CGK',
                'origin_name'      => 'Jakarta Soekarno-Hatta',
                'destination_code' => 'LOP',
                'destination_name' => 'Lombok International',
                'flight_number'    => 'IW-1872',
                'departure_time'   => '2026-08-15 06:00:00',
                'arrival_time'     => '2026-08-15 10:00:00',
                'duration_minutes' => 240,
                'is_transit'       => true,
                'transit_info'     => 'Transit di Surabaya (SUB) ±1 jam',
                'class_type'       => 'Ekonomi',
                'available_seats'  => 30,
                'price_per_ticket' => 580000,
                'status'           => 'available',
                'valid_until'      => '2026-08-15 05:00:00',
            ],

            // 🚆 Kereta Jakarta → Surabaya (buat yang mau kombinasikan)
            [
                'provider'         => 'KAI',
                'transport_mode'   => 'kereta',
                'origin_code'      => 'GMR',
                'origin_name'      => 'Stasiun Gambir, Jakarta',
                'destination_code' => 'SBY',
                'destination_name' => 'Stasiun Surabaya Gubeng',
                'flight_number'    => null,
                'departure_time'   => '2026-08-14 21:00:00',
                'arrival_time'     => '2026-08-15 05:00:00',
                'duration_minutes' => 480,
                'is_transit'       => false,
                'transit_info'     => null,
                'class_type'       => 'Eksekutif',
                'available_seats'  => 40,
                'price_per_ticket' => 450000,
                'status'           => 'available',
                'valid_until'      => '2026-08-14 20:00:00',
            ],

            // 🚢 Kapal Padang → Lombok (rute alternatif)
            [
                'provider'         => 'Pelni',
                'transport_mode'   => 'kapal',
                'origin_code'      => 'PDG',
                'origin_name'      => 'Pelabuhan Teluk Bayur, Padang',
                'destination_code' => 'TJL',
                'destination_name' => 'Pelabuhan Tanjung Luar, Lombok',
                'flight_number'    => null,
                'departure_time'   => '2026-08-14 16:00:00',
                'arrival_time'     => '2026-08-15 08:00:00',
                'duration_minutes' => 960,
                'is_transit'       => false,
                'transit_info'     => null,
                'class_type'       => 'Kelas I',
                'available_seats'  => 100,
                'price_per_ticket' => 350000,
                'status'           => 'available',
                'valid_until'      => '2026-08-14 15:00:00',
            ],

            // 🚌 Bus Surabaya → Lombok (via ferry)
            [
                'provider'         => 'Lombok Trans',
                'transport_mode'   => 'bus',
                'origin_code'      => 'SBY',
                'origin_name'      => 'Terminal Bungurasih, Surabaya',
                'destination_code' => 'MBL',
                'destination_name' => 'Terminal Mandalika, Lombok',
                'flight_number'    => null,
                'departure_time'   => '2026-08-15 08:00:00',
                'arrival_time'     => '2026-08-15 20:00:00',
                'duration_minutes' => 720,
                'is_transit'       => true,
                'transit_info'     => 'Ferry penyeberangan Banyuwangi - Lembar ±1 jam',
                'class_type'       => 'Eksekutif',
                'available_seats'  => 25,
                'price_per_ticket' => 280000,
                'status'           => 'available',
                'valid_until'      => '2026-08-15 07:00:00',
            ],

            // ✈️ Tanggal berbeda (untuk test pencarian multi-tanggal)
            [
                'provider'         => 'Lion Air',
                'transport_mode'   => 'pesawat',
                'origin_code'      => 'CGK',
                'origin_name'      => 'Jakarta Soekarno-Hatta',
                'destination_code' => 'LOP',
                'destination_name' => 'Lombok International',
                'flight_number'    => 'JT-894',
                'departure_time'   => '2026-08-16 11:00:00',
                'arrival_time'     => '2026-08-16 13:30:00',
                'duration_minutes' => 150,
                'is_transit'       => false,
                'transit_info'     => null,
                'class_type'       => 'Ekonomi',
                'available_seats'  => 35,
                'price_per_ticket' => 900000,
                'status'           => 'available',
                'valid_until'      => '2026-08-16 10:00:00',
            ],

            // ✈️ Rute Bali → Lombok
            [
                'provider'         => 'Wings Air',
                'transport_mode'   => 'pesawat',
                'origin_code'      => 'DPS',
                'origin_name'      => 'Ngurah Rai, Bali',
                'destination_code' => 'LOP',
                'destination_name' => 'Lombok International',
                'flight_number'    => 'IW-1890',
                'departure_time'   => '2026-08-15 09:00:00',
                'arrival_time'     => '2026-08-15 09:30:00',
                'duration_minutes' => 30,
                'is_transit'       => false,
                'transit_info'     => null,
                'class_type'       => 'Ekonomi',
                'available_seats'  => 12,
                'price_per_ticket' => 450000,
                'status'           => 'available',
                'valid_until'      => '2026-08-15 08:00:00',
            ],
        ];

        foreach ($tickets as $ticket) {
            TransportTicket::updateOrCreate(
                [
                    'provider' => $ticket['provider'],
                    'flight_number' => $ticket['flight_number'],
                    'departure_time' => $ticket['departure_time'],
                ],
                $ticket,
            );
        }

        $this->command->info('Transport ticket data seeded: ' . count($tickets) . ' tickets');
    }
}
