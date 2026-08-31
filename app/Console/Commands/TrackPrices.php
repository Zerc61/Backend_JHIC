<?php

namespace App\Console\Commands;

use App\Services\PriceTrackingService;
use Illuminate\Console\Command;

class TrackPrices extends Command
{
    protected $signature = 'prices:track';

    protected $description = 'Simpan snapshot harga wishlist & kirim notifikasi penurunan harga / target tercapai';

    public function handle(PriceTrackingService $priceTracking): int
    {
        $report = $priceTracking->trackAll();

        $this->info(sprintf(
            'Done. tracked=%d snapshots=%d drops=%d targets=%d',
            $report['tracked'],
            $report['snapshots'],
            $report['drops'],
            $report['targets']
        ));

        return self::SUCCESS;
    }
}