<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelBooking extends Model
{
    protected $fillable = [
        'booking_id', 'hotel_id', 'hotel_room_id', 'check_in_date',
        'check_out_date', 'number_of_rooms', 'number_of_guests',
        'guest_name', 'guest_phone', 'special_requests', 'qr_code', 'status',
    ];

    protected $casts = [
        'check_in_date'  => 'date',
        'check_out_date' => 'date',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(HotelRoom::class, 'hotel_room_id');
    }

    public function getNumberOfNightsAttribute(): int
    {
        return max(1, $this->check_in_date->diffInDays($this->check_out_date));
    }
}