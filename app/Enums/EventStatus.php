<?php

namespace App\Enums;

enum EventStatus: string
{
    case UPCOMING = 'upcoming';
    case ONGOING = 'ongoing';
    case FINISHED = 'finished';
    case CANCELLED = 'cancelled';
}