<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TripPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'start_date',
        'end_date',
        'budget',
        'duration_days',
        'total_people',
        'estimated_cost',
        'itinerary',
        'is_public',
        'share_token',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'duration_days' => 'integer',
            'total_people' => 'integer',
            'estimated_cost' => 'decimal:2',
            'itinerary' => 'array',
            'is_public' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function destinations()
    {
        return $this->belongsToMany(Destination::class, 'trip_plan_destinations')
            ->withPivot('day_number', 'sort_order', 'notes')
            ->orderByPivot('day_number')
            ->orderByPivot('sort_order');
    }

    public function days(): HasMany
    {
        return $this->hasMany(ItineraryDay::class)
            ->orderBy('date')
            ->orderBy('sort_order');
    }

    public function items()
    {
        return ItineraryItem::whereIn('day_id', $this->days()->select('id'));
    }

    public function ensureShareToken(): string
    {
        if (empty($this->share_token)) {
            do {
                $token = 'trp_' . Str::random(40);
            } while (self::where('share_token', $token)->exists());

            $this->forceFill(['share_token' => $token])->save();
        }

        return $this->share_token;
    }

    public function isOwner(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}