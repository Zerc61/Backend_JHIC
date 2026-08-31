<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_item_id',
        'price',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function wishlistItem(): BelongsTo
    {
        return $this->belongsTo(WishlistItem::class, 'wishlist_item_id');
    }
}