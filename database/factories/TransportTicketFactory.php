<?php

namespace Database\Factories;

use App\Enums\TransportMode;
use App\Models\TransportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class TransportTicketFactory extends Factory
{
    protected $model = TransportTicket::class;

    public function definition(): array
    {
        $modeRoll = $this->faker->numberBetween(1, 100);
        $mode = match (true) {
            $modeRoll <= 60 => TransportMode::PESAWAT,
            $modeRoll <= 75 => TransportMode::KERETA,
            $modeRoll <= 90 => TransportMode::BUS,
            default         => TransportMode::KAPAL,
        };

        $route = $this->pickRoute($mode);
        $isTransit = $mode === TransportMode::PESAWAT && $this->faker->boolean(20);

        $departureHour = $mode === TransportMode::KERETA
            ? $this->faker->numberBetween(5, 23)
            : $this->faker->numberBetween(6, 20);

        $departureMinute = $mode === TransportMode::KERETA
            ? 0
            : ($this->faker->boolean() ? 0 : 30);

        // ✅ DIUBAH: tersebar 1-365 hari, bukan 5-60
        $departureTime = now()
            ->addDays(rand(1, 365))
            ->setHour($departureHour)
            ->setMinute($departureMinute)
            ->setSecond(0);

        $durationMinutes = $this->getDuration($mode, $isTransit);
        $arrivalTime = $departureTime->copy()->addMinutes($durationMinutes);

        $provider = $this->getProvider($mode);

        $flightNumber = $mode === TransportMode::PESAWAT
            ? $this->generateFlightNumber($provider)
            : null;

        $basePrice = $this->getBasePrice($mode, $durationMinutes);
        $pricePerTicket = $basePrice + ($this->faker->numberBetween(0, 5) * 50000);

        $classType = match ($mode) {
            TransportMode::PESAWAT => $this->faker->randomElement(['Ekonomi', 'Ekonomi', 'Ekonomi', 'Bisnis']),
            TransportMode::KERETA  => $this->faker->randomElement(['Ekonomi', 'Ekonomi', 'Bisnis', 'Eksekutif']),
            TransportMode::BUS     => $this->faker->randomElement(['Ekonomi', 'Eksekutif']),
            TransportMode::KAPAL   => $this->faker->randomElement(['Kelas III', 'Kelas II', 'Kelas I']),
        };

        $transitInfo = null;
        if ($isTransit) {
            $transitCities = ['Surabaya (SUB)', 'Denpasar (DPS)', 'Makassar (UPG)', 'Balikpapan (BPN)', 'Yogyakarta (YIA)'];
            $transitCity = $this->faker->randomElement($transitCities);
            $transitInfo = "Transit di {$transitCity} ±" . $this->faker->numberBetween(1, 3) . " jam";
            $arrivalTime->addMinutes($this->faker->numberBetween(60, 180));
        }

        return [
            'provider'         => $provider,
            'transport_mode'   => $mode->value,
            'origin_code'      => $route['origin_code'],
            'origin_name'      => $route['origin_name'],
            'destination_code' => $route['destination_code'],
            'destination_name' => $route['destination_name'],
            'flight_number'    => $flightNumber,
            'departure_time'   => $departureTime,
            'arrival_time'     => $arrivalTime,
            'duration_minutes' => $departureTime->diffInMinutes($arrivalTime),
            'is_transit'       => $isTransit,
            'transit_info'     => $transitInfo,
            'class_type'       => $classType,
            'available_seats'  => $this->faker->numberBetween(5, 80),
            'price_per_ticket' => $pricePerTicket,
            'status'           => 'available',
            'valid_until'      => $departureTime->copy()->subHour(),
        ];
    }

    // ============================================================
    // STATE: Atur tanggal & jam secara presisi
    // Digunakan oleh seeder untuk tiket guarantee
    // ============================================================
    public function scheduledOn(Carbon $date, int $hour, int $minute = 0, ?int $duration = null): static
    {
        return $this->state(function (array $attributes) use ($date, $hour, $minute, $duration) {
            $finalDuration = $duration ?? $attributes['duration_minutes'];
            $departure = $date->copy()->setHour($hour)->setMinute($minute)->setSecond(0);
            $arrival = $departure->copy()->addMinutes($finalDuration);

            return [
                'departure_time'   => $departure,
                'arrival_time'     => $arrival,
                'duration_minutes' => $finalDuration,
                'valid_until'      => $departure->copy()->subHour(),
            ];
        });
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function pickRoute(TransportMode $mode): array
    {
        $routes = match ($mode) {
            TransportMode::PESAWAT => [
                ['origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International'],
                ['origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'DPS', 'destination_name' => 'Ngurah Rai, Bali'],
                ['origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'SUB', 'destination_name' => 'Juanda, Surabaya'],
                ['origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'UPG', 'destination_name' => 'Sultan Hasanuddin, Makassar'],
                ['origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'BPN', 'destination_name' => 'Sultan Aji M. Sulaiman, Balikpapan'],
                ['origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'KNO', 'destination_name' => 'Kualanamu, Medan'],
                ['origin_code' => 'CGK', 'origin_name' => 'Jakarta Soekarno-Hatta', 'destination_code' => 'PDG', 'destination_name' => 'Minangkabau, Padang'],
                ['origin_code' => 'DPS', 'origin_name' => 'Ngurah Rai, Bali', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International'],
                ['origin_code' => 'SUB', 'origin_name' => 'Juanda, Surabaya', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International'],
                ['origin_code' => 'SUB', 'origin_name' => 'Juanda, Surabaya', 'destination_code' => 'DPS', 'destination_name' => 'Ngurah Rai, Bali'],
                ['origin_code' => 'UPG', 'origin_name' => 'Sultan Hasanuddin, Makassar', 'destination_code' => 'LOP', 'destination_name' => 'Lombok International'],
                ['origin_code' => 'BPN', 'origin_name' => 'Sultan Aji M. Sulaiman, Balikpapan', 'destination_code' => 'SUB', 'destination_name' => 'Juanda, Surabaya'],
            ],
            TransportMode::KERETA => [
                ['origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'SBY', 'destination_name' => 'Stasiun Surabaya Gubeng'],
                ['origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'BD', 'destination_name' => 'Stasiun Bandung'],
                ['origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'YK', 'destination_name' => 'Stasiun Yogyakarta'],
                ['origin_code' => 'GMR', 'origin_name' => 'Stasiun Gambir, Jakarta', 'destination_code' => 'SM', 'destination_name' => 'Stasiun Semarang Tawang'],
                ['origin_code' => 'SBY', 'origin_name' => 'Stasiun Surabaya Gubeng', 'destination_code' => 'YK', 'destination_name' => 'Stasiun Yogyakarta'],
                ['origin_code' => 'SBY', 'origin_name' => 'Stasiun Surabaya Gubeng', 'destination_code' => 'ML', 'destination_name' => 'Stasiun Malang'],
            ],
            TransportMode::BUS => [
                ['origin_code' => 'SBY', 'origin_name' => 'Terminal Bungurasih, Surabaya', 'destination_code' => 'MBL', 'destination_name' => 'Terminal Mandalika, Lombok'],
                ['origin_code' => 'SBY', 'origin_name' => 'Terminal Bungurasih, Surabaya', 'destination_code' => 'DPS', 'destination_name' => 'Terminal Mengwi, Bali'],
                ['origin_code' => 'BD', 'origin_name' => 'Terminal Leuwi Panjang, Bandung', 'destination_code' => 'SBY', 'destination_name' => 'Terminal Bungurasih, Surabaya'],
                ['origin_code' => 'YK', 'origin_name' => 'Terminal Giwangan, Yogyakarta', 'destination_code' => 'SBY', 'destination_name' => 'Terminal Bungurasih, Surabaya'],
                ['origin_code' => 'JKT', 'origin_name' => 'Terminal Pulo Gebang, Jakarta', 'destination_code' => 'SBY', 'destination_name' => 'Terminal Bungurasih, Surabaya'],
                ['origin_code' => 'JKT', 'origin_name' => 'Terminal Pulo Gebang, Jakarta', 'destination_code' => 'BD', 'destination_name' => 'Terminal Leuwi Panjang, Bandung'],
                ['origin_code' => 'MBL', 'origin_name' => 'Terminal Mandalika, Lombok', 'destination_code' => 'DPS', 'destination_name' => 'Terminal Mengwi, Bali'],
            ],
            TransportMode::KAPAL => [
                ['origin_code' => 'PDG', 'origin_name' => 'Pelabuhan Teluk Bayur, Padang', 'destination_code' => 'TJL', 'destination_name' => 'Pelabuhan Tanjung Luar, Lombok'],
                ['origin_code' => 'SBY', 'origin_name' => 'Pelabuhan Tanjung Perak, Surabaya', 'destination_code' => 'MKS', 'destination_name' => 'Pelabuhan Soekarno-Hatta, Makassar'],
                ['origin_code' => 'SBY', 'origin_name' => 'Pelabuhan Tanjung Perak, Surabaya', 'destination_code' => 'AMB', 'destination_name' => 'Pelabuhan Yos Sudarso, Ambon'],
                ['origin_code' => 'MKS', 'origin_name' => 'Pelabuhan Soekarno-Hatta, Makassar', 'destination_code' => 'TJL', 'destination_name' => 'Pelabuhan Tanjung Luar, Lombok'],
                ['origin_code' => 'JKT', 'origin_name' => 'Pelabuhan Tanjung Priok, Jakarta', 'destination_code' => 'SBY', 'destination_name' => 'Pelabuhan Tanjung Perak, Surabaya'],
                ['origin_code' => 'BAT', 'origin_name' => 'Pelabuhan Sekupang, Batam', 'destination_code' => 'SIN', 'destination_name' => 'Pelabuhan Harbour Front, Singapura'],
            ],
        };

        return $this->faker->randomElement($routes);
    }

    private function getProvider(TransportMode $mode): string
    {
        return match ($mode) {
            TransportMode::PESAWAT => $this->faker->randomElement([
                'Lion Air', 'Garuda Indonesia', 'Citilink', 'Wings Air',
                'Batik Air', 'Sriwijaya Air', 'NAM Air', 'Indonesia AirAsia',
            ]),
            TransportMode::KERETA => 'Kereta Api Indonesia (KAI)',
            TransportMode::BUS => $this->faker->randomElement([
                'Lombok Trans', 'Pahala Kencana', 'Rosalia Indah',
                'Sinar Jaya', 'Garuda Mas', 'AKAS', 'Lorena',
            ]),
            TransportMode::KAPAL => $this->faker->randomElement([
                'Pelni', 'Dharma Lautan Utama', 'Djakarta Lloyd',
                'Ferry Express', 'PT Pelayaran Nasional',
            ]),
        };
    }

    private function generateFlightNumber(string $provider): string
    {
        $prefixes = match ($provider) {
            'Lion Air'           => ['JT'],
            'Garuda Indonesia'  => ['GA'],
            'Citilink'          => ['QG'],
            'Wings Air'         => ['IW'],
            'Batik Air'         => ['ID'],
            'Sriwijaya Air'     => ['SJ'],
            'NAM Air'           => ['IN'],
            'Indonesia AirAsia' => ['QZ'],
            default             => ['XX'],
        };

        $prefix = $this->faker->randomElement($prefixes);
        $number = $this->faker->numberBetween(100, 999);

        return "{$prefix}-{$number}";
    }

    private function getDuration(TransportMode $mode, bool $isTransit): int
    {
        $base = match ($mode) {
            TransportMode::PESAWAT => 150,
            TransportMode::KERETA  => 480,
            TransportMode::BUS     => 600,
            TransportMode::KAPAL   => 960,
        };

        $variation = $base * ($this->faker->numberBetween(-20, 20) / 100);

        return (int) ($base + $variation);
    }

    private function getBasePrice(TransportMode $mode, int $durationMinutes): int
    {
        $perMinute = match ($mode) {
            TransportMode::PESAWAT => 5500,
            TransportMode::KERETA  => 900,
            TransportMode::BUS     => 450,
            TransportMode::KAPAL   => 350,
        };

        $raw = $perMinute * $durationMinutes;
        return (int) (round($raw / 50000) * 50000);
    }
}