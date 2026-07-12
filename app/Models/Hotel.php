<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Hotel extends Model
{
    protected $fillable = [
        'manager_id', 'destination_id', 'name', 'slug', 'description',
        'address', 'latitude', 'longitude', 'star_rating', 'phone',
        'website', 'check_in_time', 'check_out_time', 'thumbnail', 'status',
    ];

    protected $casts = [
        'latitude'       => 'decimal:8',
        'longitude'      => 'decimal:8',
        'star_rating'    => 'integer',
        'check_in_time'  => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HotelRoom::class);
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(HotelGallery::class)->orderBy('sort_order');
    }

    public function hotelBookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function wishlists(): MorphMany
    {
        return $this->morphMany(Wishlist::class, 'wishlistable');
    }

    // Scope
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}