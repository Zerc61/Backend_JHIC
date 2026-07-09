<?php

namespace App\Models;

use App\Enums\UmkmStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Umkm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'destination_id',
        'umkm_category_id',
        'name',
        'slug',
        'description',
        'address',
        'latitude',
        'longitude',
        'phone',
        'opening_hours',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'status' => UmkmStatus::class,
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
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
}