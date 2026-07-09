<?php

namespace App\Models;

use App\Enums\DestinationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination_category_id',
        'manager_id',
        'name',
        'slug',
        'description',
        'address',
        'latitude',
        'longitude',
        'open_hour',
        'close_hour',
        'ticket_price',
        'phone',
        'website',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'ticket_price' => 'decimal:2',
            'status' => DestinationStatus::class,
        ];
    }

        public function category()
    {
        return $this->belongsTo(DestinationCategory::class, 'destination_category_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function galleries()
    {
        return $this->hasMany(DestinationGallery::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'destination_facility');
    }

    public function umkms()
    {
        return $this->hasMany(Umkm::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function tripPlans()
    {
        return $this->belongsToMany(TripPlan::class, 'trip_plan_destinations')
            ->withPivot('day_number', 'sort_order', 'notes');
    }

    public function getMainImageAttribute(): ?string
    {
        return $this->galleries()->orderBy('sort_order')->first()?->image;
    }

    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
}