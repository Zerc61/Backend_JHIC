<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'budget',
        'duration_days',
        'total_people',
        'estimated_cost',
        'itinerary',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'duration_days' => 'integer',
            'total_people' => 'integer',
            'estimated_cost' => 'decimal:2',
            'itinerary' => 'array',
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
}