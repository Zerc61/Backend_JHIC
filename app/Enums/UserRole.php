<?php

namespace App\Enums;

enum UserRole: string
{
    case TOURIST = 'tourist';
    case UMKM = 'umkm';
    case MANAGER = 'manager';
    case ADMIN = 'admin';
}