<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageBooking extends Model
{
    protected $fillable = [
        'booking_id', 'travel_package_id', 'schedule_id',
        'total_travelers', 'traveler_names', 'contact_person',
        'contact_phone', 'notes', 'status',
    ];

    protected $casts = [
        'traveler_names' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function travelPackage(): BelongsTo
    {
        return $this->belongsTo(TravelPackage::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TravelPackageSchedule::class, 'schedule_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageBookingItem::class)->orderBy('sort_order');
    }
}  