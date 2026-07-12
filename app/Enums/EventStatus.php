<?php

namespace App\Enums;

enum EventStatus: string
{
    case UPCOMING = 'upcoming';
    case ONGOING = 'ongoing';
    case FINISHED = 'finished';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::UPCOMING => 'Akan Datang',
            self::ONGOING => 'Berlangsung',
            self::FINISHED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::UPCOMING => 'bg-blue-100 text-blue-700',
            self::ONGOING => 'bg-emerald-100 text-emerald-700',
            self::FINISHED => 'bg-slate-100 text-slate-500',
            self::CANCELLED => 'bg-red-100 text-red-600',
        };
    }
}