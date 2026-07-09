<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case PICKED_UP = 'picked_up';
    case CANCELLED = 'cancelled';
}