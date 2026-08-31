<?php

namespace App\Console\Commands;

use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class ExpireLoyaltyCoins extends Command
{
    protected $signature = 'loyalty:expire';

    protected $description = 'Menandai reward EJTCoin yang kedaluwarsa dan menyesuaikan saldo wallet';

    public function handle(LoyaltyService $loyalty): int
    {
        $count = $loyalty->expireCoins();

        $this->info("Selesai. Coin kedaluwarsa yang diproses: {$count}");

        return self::SUCCESS;
    }
}