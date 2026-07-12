<?php

// app/Models/TravelPackageSchedule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelPackageSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'travel_package_id',
        'departure_date',
        'return_date',
        'max_capacity',
        'current_booked',
        'status',
        'notes',
        // pickup_location, pickup_time, vehicle_info, driver_name, driver_phone ← DIHAPUS
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date'    => 'date',
        'max_capacity'   => 'integer',
        'current_booked' => 'integer',
    ];

    // ── Relations ───────────────────────────────────

    public function travelPackage(): BelongsTo
    {
        return $this->belongsTo(TravelPackage::class);
    }

    public function packageBookings(): HasMany
    {
        return $this->hasMany(PackageBooking::class, 'schedule_id');
    }

    // ── Helpers ─────────────────────────────────────

    public function getRemainingSlots(): int
    {
        return max(0, $this->max_capacity - $this->current_booked);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available'
            && $this->getRemainingSlots() > 0
            && $this->departure_date->isFuture();
    }
}
