<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelGallery extends Model
{
    protected $fillable = ['hotel_id', 'image', 'caption', 'sort_order'];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}