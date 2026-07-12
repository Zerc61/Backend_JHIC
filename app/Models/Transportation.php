<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Transportation extends Model
{
    protected $fillable = [
        'manager_id', 'destination_id', 'name', 'slug', 'type',
        'description', 'capacity', 'price_per_day', 'includes_driver',
        'includes_fuel', 'thumbnail', 'phone', 'status',
    ];

    protected $casts = [
        'price_per_day'  => 'decimal:2',
        'includes_driver' => 'boolean',
        'includes_fuel'   => 'boolean',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(TransportationGallery::class)->orderBy('sort_order');
    }

    public function transportationBookings(): HasMany
    {
        return $this->hasMany(TransportationBooking::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function wishlists(): MorphMany
    {
        return $this->morphMany(Wishlist::class, 'wishlistable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}