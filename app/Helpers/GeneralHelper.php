<?php

namespace App\Helpers;

class GeneralHelper
{
    public static function formatRupiah(float|int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public static function formatCoin(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 4, ',', '.'), '0'), ',') . ' Coin';
    }

    public static function calculateCoins(float $rupiah, float $ratePerCoin = 2000): float
    {
        return floor(($rupiah / $ratePerCoin) * 10000) / 10000;
    }
}