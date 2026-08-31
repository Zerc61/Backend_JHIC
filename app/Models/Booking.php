<?php

// app/Models/Booking.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use App\Models\DestinationTicketBooking;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'user_id',
        'booking_type',
        'status',
        'total_price',
        'coin_amount',
        'coin_to_rupiah_rate',
        'rupiah_equivalent',
        'voucher_id',
        'discount',
        'total_amount',
        'notes',
        'paid_at',
        'cancelled_at',
    ];

    protected $casts = [
        'total_price'        => 'decimal:2',
        'coin_amount'        => 'decimal:4',
        'coin_to_rupiah_rate' => 'decimal:2',
        'rupiah_equivalent'  => 'decimal:2',
        'discount'           => 'decimal:2',
        'total_amount'       => 'decimal:2',
        'paid_at'            => 'datetime',
        'cancelled_at'       => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = self::generateBookingNumber();
            }
        });
    }

    // ── Scope ───────────────────────────────────────

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ── Relations ───────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotelBooking(): HasOne
    {
        return $this->hasOne(HotelBooking::class);
    }

    public function ticketBookings(): HasMany
    {
        return $this->hasMany(TransportTicketBooking::class);
    }

    public function packageBooking(): HasOne
    {
        return $this->hasOne(PackageBooking::class);
    }

    public function transportationBooking(): HasOne
    {
        return $this->hasOne(TransportationBooking::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    // ── Accessor ────────────────────────────────────


    // ── Helper ──────────────────────────────────────

    private static function generateBookingNumber(): string
    {
        $prefix = 'BKG';
        $date = now()->format('Ymd');
        $random = strtoupper(\Illuminate\Support\Str::random(5));

        return "{$prefix}-{$date}-{$random}";
    }
// ── Tambahkan relation ini ──
public function destinationTicketBooking(): HasOne
{
    return $this->hasOne(DestinationTicketBooking::class);
}

// ── Update method getDetailAttribute() ──
public function getDetailAttribute()
{
    return match ($this->booking_type) {
        'hotel'              => $this->hotelBooking,
        'transportation'     => $this->transportationBooking,
        'transport_ticket'   => $this->ticketBookings->first()?->transportTicket,
        'travel_package'     => $this->packageBooking,
        'destination_ticket' => $this->destinationTicketBooking,  // <-- BARU
        default              => null,
    };
}

public function getRouteKeyName(): string
    {
        return 'booking_number';
    }
}