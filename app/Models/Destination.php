<?php

namespace App\Models;

use App\Enums\DestinationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'estimated_cost', // <-- tambahkan
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

    // Generate unique slug
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    // Route binding pakai slug
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Relasi
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
        if ($this->relationLoaded('galleries') && $this->galleries->isNotEmpty()) {
            return $this->galleries->first()->image;
        }
        return null;
    }

    public function getAverageRatingAttribute(): float
    {
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->avg('rating') ?? 0;
        }
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function ticketBookings()
    {
        return $this->hasMany(DestinationTicketBooking::class);
    }


     public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where('name', 'like', "%{$search}%");
        });

        $query->when($filters['status'] ?? null, function ($q, $status) {
            $q->where('status', $status);
        });

        $query->when($filters['category_id'] ?? null, function ($q, $categoryId) {
            $q->where('destination_category_id', $categoryId);
        });

        return $query;
    }

    
}