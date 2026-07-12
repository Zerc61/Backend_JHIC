<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TravelPackage extends Model
{
      protected $fillable = [
        'destination_id',
        'hotel_id',
        // 'transportation_id' ← DIHAPUS
        'name',
        'slug',
        'description',
        'thumbnail',
        'duration_days',
        'duration_nights',
        'price_per_person',
        'included_items',
        'excluded_items',
        'meals_included',
        'benefits',         // ← BARU
        'terms_conditions',
        'status',
    ];

    protected $casts = [
        'price_per_person' => 'decimal:2',
        'included_items'   => 'array',
        'excluded_items'   => 'array',
        'meals_included'   => 'array',
        'benefits'         => 'array',   // ← BARU
        'terms_conditions' => 'array',
    ];
    
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function transportation(): BelongsTo
    {
        return $this->belongsTo(Transportation::class);
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(TravelPackageGallery::class)->orderBy('sort_order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TravelPackageSchedule::class)->orderBy('departure_date');
    }

    public function packageBookings(): HasMany
    {
        return $this->hasMany(PackageBooking::class);
    }

    public function wishlists(): MorphMany
    {
        return $this->morphMany(Wishlist::class, 'wishlistable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeHasAvailableSchedule($query)
    {
        return $query->whereHas('schedules', function ($q) {
            $q->where('status', 'available')
              ->whereColumn('current_booked', '<', 'max_capacity')
              ->where('departure_date', '>=', now()->toDateString());
        });
    }
}