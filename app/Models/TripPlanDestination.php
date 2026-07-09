<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TripPlanDestination extends Pivot
{
    use HasFactory;

    protected $table = 'trip_plan_destinations';

    protected $fillable = [
        'trip_plan_id',
        'destination_id',
        'day_number',
        'sort_order',
        'notes',
    ];

    public function tripPlan()
    {
        return $this->belongsTo(TripPlan::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}