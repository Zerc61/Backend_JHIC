<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItineraryDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_plan_id',
        'date',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function tripPlan(): BelongsTo
    {
        return $this->belongsTo(TripPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItineraryItem::class, 'day_id')
            ->orderBy('slot')
            ->orderBy('sort_order');
    }

    public function itemsBySlot(string $slot)
    {
        return $this->hasMany(ItineraryItem::class, 'day_id')
            ->where('slot', $slot)
            ->orderBy('sort_order');
    }
}