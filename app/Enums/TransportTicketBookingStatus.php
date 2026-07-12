<?php

namespace App\Enums;

enum TransportTicketBookingStatus: string
{
    case CONFIRMED = 'confirmed';
    case ISSUED    = 'issued';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMED => 'Dikonfirmasi',
            self::ISSUED    => 'Tiket Terbit',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}