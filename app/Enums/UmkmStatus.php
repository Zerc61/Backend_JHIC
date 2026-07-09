<?php

namespace App\Enums;

enum UmkmStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
    case REJECTED = 'rejected';
}