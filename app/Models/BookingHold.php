<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BookingHold extends Model
{
    protected $fillable = [
        'user_id',
        'booking_id',
        'holdable_type',
        'holdable_id',
        'quantity',
        'held_at',
        'expires_at',
        'released_at',
        'status',
        'release_reason',
        'metadata',
    ];

    protected $casts = [
        'held_at' => 'datetime',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Create hold for hotel room
     */
    public static function createForHotelRoom(Booking $booking, $roomId, $quantity = 1, $holdMinutes = 30): self
    {
        return self::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'holdable_type' => 'HotelRoom',
            'holdable_id' => $roomId,
            'quantity' => $quantity,
            'held_at' => now(),
            'expires_at' => now()->addMinutes($holdMinutes),
            'status' => 'active',
            'metadata' => [
                'booking_type' => 'hotel',
                'check_in' => $booking->hotelBooking?->check_in_date,
                'check_out' => $booking->hotelBooking?->check_out_date,
            ],
        ]);
    }

    /**
     * Create hold for transportation
     */
    public static function createForTransportation(Booking $booking, $transportationId, $quantity = 1, $holdMinutes = 30): self
    {
        return self::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'holdable_type' => 'Transportation',
            'holdable_id' => $transportationId,
            'quantity' => $quantity,
            'held_at' => now(),
            'expires_at' => now()->addMinutes($holdMinutes),
            'status' => 'active',
            'metadata' => [
                'booking_type' => 'transportation',
                'start_date' => $booking->transportationBooking?->start_date,
                'end_date' => $booking->transportationBooking?->end_date,
            ],
        ]);
    }

    /**
     * Create hold for package schedule
     */
    public static function createForPackageSchedule(Booking $booking, $scheduleId, $quantity = 1, $holdMinutes = 30): self
    {
        return self::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'holdable_type' => 'PackageSchedule',
            'holdable_id' => $scheduleId,
            'quantity' => $quantity,
            'held_at' => now(),
            'expires_at' => now()->addMinutes($holdMinutes),
            'status' => 'active',
            'metadata' => [
                'booking_type' => 'travel_package',
                'total_travelers' => $booking->packageBooking?->total_travelers,
            ],
        ]);
    }

    /**
     * Check if hold is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'active' && now()->isAfter($this->expires_at);
    }

    /**
     * Release hold
     */
    public function release(string $reason = 'manual'): self
    {
        $this->update([
            'status' => 'released',
            'released_at' => now(),
            'release_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Release hold due to expiration
     */
    public function expireHold(): self
    {
        $this->update([
            'status' => 'expired',
            'released_at' => now(),
            'release_reason' => 'expired',
        ]);

        return $this;
    }

    /**
     * Release all expired holds for a booking
     */
    public static function releaseExpiredForBooking($bookingId): int
    {
        $expired = self::where('booking_id', $bookingId)
            ->expired()
            ->get();

        $count = 0;
        foreach ($expired as $hold) {
            $hold->expireHold();
            $count++;
        }

        return $count;
    }

    /**
     * Release all holds for a booking (on successful payment)
     */
    public static function releaseForBooking($bookingId, string $reason = 'payment_completed'): int
    {
        return self::where('booking_id', $bookingId)
            ->active()
            ->update([
                'status' => 'released',
                'released_at' => now(),
                'release_reason' => $reason,
            ]);
    }

    /**
     * Check if item is available (not held by others)
     */
    public static function isItemAvailable($holdableType, $holdableId, $quantity = 1, $excludeBookingId = null): bool
    {
        $query = self::where('holdable_type', $holdableType)
            ->where('holdable_id', $holdableId)
            ->active();

        if ($excludeBookingId) {
            $query->where('booking_id', '!=', $excludeBookingId);
        }

        $heldQuantity = $query->sum('quantity');

        // Assuming each item has max capacity (simplified)
        $maxCapacity = 10; // This should be dynamic based on item type

        return ($heldQuantity + $quantity) <= $maxCapacity;
    }

    /**
     * Get total held quantity for item
     */
    public static function getHeldQuantity($holdableType, $holdableId): int
    {
        return self::where('holdable_type', $holdableType)
            ->where('holdable_id', $holdableId)
            ->active()
            ->sum('quantity');
    }
}
