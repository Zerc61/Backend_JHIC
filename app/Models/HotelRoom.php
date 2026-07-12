<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotelRoom extends Model
{
    protected $fillable = [
        'hotel_id', 'name', 'description', 'capacity',
        'price_per_night', 'total_rooms', 'amenities', 'status',
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'amenities'       => 'array',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function hotelBookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}