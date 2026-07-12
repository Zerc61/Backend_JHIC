<?php

namespace App\Enums;

enum TransportTicketStatus: string
{
    case AVAILABLE = 'available';
    case SOLD_OUT = 'sold_out';
    case EXPIRED  = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::SOLD_OUT => 'Habis',
            self::EXPIRED  => 'Kadaluarsa',
        };
    }
}