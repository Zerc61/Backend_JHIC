<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pantau harga wishlist: snapshot + notifikasi penurunan harga (setiap 6 jam)
Schedule::command('prices:track')->everySixHours();

// Kadaluarsakan coin loyalty yang melewati masa berlaku (harian)
Schedule::command('loyalty:expire')->dailyAt('03:00');