<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'wishlistable_type',
        'wishlistable_id',
        'target_price',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'target_price' => 'decimal:2',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(WishlistCollection::class, 'collection_id');
    }

    public function wishlistable(): MorphTo
    {
        return $this->morphTo();
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class, 'wishlist_item_id')
            ->orderBy('recorded_at');
    }
}