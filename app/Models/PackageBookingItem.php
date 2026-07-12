<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageBookingItem extends Model
{
    protected $fillable = [
        'package_booking_id', 'item_type', 'title', 'description',
        'qr_code', 'qr_data', 'sort_order',
    ];

    protected $casts = [
        'qr_data' => 'array',
    ];

    public function packageBooking(): BelongsTo
    {
        return $this->belongsTo(PackageBooking::class);
    }
}