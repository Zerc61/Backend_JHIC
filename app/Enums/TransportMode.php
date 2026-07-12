<?php

namespace App\Enums;

enum TransportMode: string
{
    case PESAWAT = 'pesawat';
    case KERETA  = 'kereta';
    case BUS     = 'bus';
    case KAPAL   = 'kapal';

    public function label(): string
    {
        return match ($this) {
            self::PESAWAT => 'Pesawat',
            self::KERETA  => 'Kereta Api',
            self::BUS     => 'Bus',
            self::KAPAL   => 'Kapal Laut',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PESAWAT => '✈️',
            self::KERETA  => '🚆',
            self::BUS     => '🚌',
            self::KAPAL   => '🚢',
        };
    }
}