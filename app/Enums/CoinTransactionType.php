<?php

namespace App\Enums;

enum CoinTransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case EARN = 'earn';
    case REDEEM = 'redeem';
    case EXPIRE = 'expire';
    case ADJUST = 'adjust';
}