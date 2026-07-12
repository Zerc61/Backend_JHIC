<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportTicketBooking extends Model
{
    protected $fillable = [
        'booking_id', 'transport_ticket_id', 'passenger_name',
        'passenger_id_type', 'passenger_id_number', 'seat_number',
        'ticket_number', 'provider_booking_code', 'qr_code', 'status',
        'issued_at', 'raw_response',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function transportTicket(): BelongsTo
    {
        return $this->belongsTo(TransportTicket::class);
    }
}
