<?php

namespace App\Models;

use App\Enums\UmkmStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Umkm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'destination_id', 'umkm_category_id', 'name', 'slug',
        'description', 'address', 'latitude', 'longitude', 'phone',
        'opening_hours', 'status', 'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'status' => UmkmStatus::class,
        ];
    }

    // --- Accessor: cover image dari produk pertama ---
    public function getCoverImageAttribute(): ?string
    {
        if ($this->relationLoaded('products') && $this->products->isNotEmpty()) {
            return $this->products->first()->image;
        }
        return null;
    }

    // --- Accessor: average rating ---
    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    // --- Relationships ---
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function category()
    {
        return $this->belongsTo(UmkmCategory::class, 'umkm_category_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }
}