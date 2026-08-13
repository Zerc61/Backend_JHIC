<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestinationTicketBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'destination_id',
        'visit_date',
        'number_of_visitors',
        'visitor_names',
        'contact_person',
        'contact_phone',
        'qr_code',
        'status',
    ];

    protected $casts = [
        'visit_date'    => 'date',
        'visitor_names' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}