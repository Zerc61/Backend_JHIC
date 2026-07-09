<?php

namespace App\Enums;

enum CoinTransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}