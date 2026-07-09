<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case COIN = 'coin';
    case CASH_ON_PICKUP = 'cash_on_pickup';
}