<?php

namespace App\Enums;

enum ProductStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
}