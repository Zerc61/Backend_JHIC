<?php
// app/Services/MidtransService.php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class MidtransService
{
    public function __construct()
    {
        $this->configure();
    }

    private function configure(): void
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createSnapToken(array $params): string
    {
        return Snap::getSnapToken($params);
    }

    public function getNotification(): Notification
    {
        return new Notification();
    }

    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $serverKey): bool
    {
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        
        $requestSignature = request()->header('X-Callback-Signature') 
            ?? request()->input('signature_key');
        
        return hash_equals($signature, $requestSignature);
    }
}