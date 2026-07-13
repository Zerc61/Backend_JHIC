<?php

namespace App\Models;

use App\Enums\TransportMode;
use App\Enums\TransportTicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'transport_mode',
        'origin_code',
        'origin_name',
        'destination_code',
        'destination_name',
        'flight_number',
        'departure_time',
        'arrival_time',
        'duration_minutes',
        'is_transit',
        'transit_info',
        'class_type',
        'available_seats',
        'price_per_ticket',
        'status',
        'valid_until',
        'raw_response',
    ];

    protected $casts = [
        'departure_time'   => 'datetime',
        'arrival_time'     => 'datetime',
        'is_transit'       => 'boolean',
        'available_seats'  => 'integer',
        'price_per_ticket' => 'decimal:2',
        'valid_until'      => 'datetime',
        'raw_response'     => 'array',
    ];

    // ── Scopes ──────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', TransportTicketStatus::AVAILABLE)
            ->where('available_seats', '>', 0)
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>', now());
            });
    }

    public function scopeUpcoming($query)
    {
        return $query->where('departure_time', '>=', now())
            ->orderBy('departure_time', 'asc');
    }

    public function scopeForRoute($query, string $origin, string $destination)
    {
        return $query->where('origin_code', $origin)
            ->where('destination_code', $destination);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('departure_time', $date);
    }

    public function scopeByMode($query, ?string $mode)
    {
        if ($mode) {
            return $query->where('transport_mode', $mode);
        }
        return $query;
    }

    /**
     * Cari berdasarkan query string (origin/destination name)
     */
    public function scopeSearchQuery($query, ?string $q)
    {
        if ($q) {
            return $query->where(function ($sub) use ($q) {
                $sub->where('origin_name', 'like', "%{$q}%")
                    ->orWhere('destination_name', 'like', "%{$q}%")
                    ->orWhere('origin_code', 'like', "%{$q}%")
                    ->orWhere('destination_code', 'like', "%{$q}%")
                    ->orWhere('provider', 'like', "%{$q}%");
            });
        }
        return $query;
    }

    /**
     * Filter harga minimum
     */
    public function scopeMinPrice($query, ?int $price)
    {
        if ($price) {
            return $query->where('price_per_ticket', '>=', $price);
        }
        return $query;
    }

    /**
     * Filter harga maksimum
     */
    public function scopeMaxPrice($query, ?int $price)
    {
        if ($price) {
            return $query->where('price_per_ticket', '<=', $price);
        }
        return $query;
    }

    /**
     * Hanya direct (tanpa transit)
     */
    public function scopeDirectOnly($query, bool $direct)
    {
        if ($direct) {
            return $query->where('is_transit', false);
        }
        return $query;
    }

    /**
     * Hanya yang masih ada kursi untuk N penumpang
     */
    public function scopeHasSeatsFor($query, int $passengers)
    {
        return $query->where('available_seats', '>=', $passengers);
    }

    // ── Relations ───────────────────────────────────

    public function bookings(): HasMany
    {
        return $this->hasMany(TransportTicketBooking::class);
    }

    // ── Helpers ─────────────────────────────────────

    public function getModeEnum(): TransportMode
    {
        return TransportMode::from($this->transport_mode);
    }

    public function getStatusEnum(): TransportTicketStatus
    {
        return TransportTicketStatus::from($this->status);
    }

    public function getRouteLabel(): string
    {
        return "{$this->origin_code} -> {$this->destination_code}";
    }

    public function getDurationLabel(): string
    {
        $hours = intdiv($this->duration_minutes, 60);
        $mins = $this->duration_minutes % 60;

        if ($hours > 0 && $mins > 0) return "{$hours}jam {$mins}menit";
        if ($hours > 0) return "{$hours}jam";
        return "{$mins}menit";
    }

    public function hasEnoughSeats(int $passengers): bool
    {
        return $this->available_seats >= $passengers;
    }

    /**
     * Ambil tanggal keberangkatan dalam format Indonesia
     */
    public function getDepartureDateLabel(): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $d = $this->departure_time;
        return "{$days[$d->dayOfWeek]}, {$d->day} {$months[$d->month]} {$d->year}";
    }

    /**
     * Cek apakah tanggal keberangkatan besok
     */
    public function isTomorrow(): bool
    {
        return $this->departure_time->isTomorrow();
    }

    /**
     * Cek apakah tanggal keberangkatan hari ini
     */
    public function isToday(): bool
    {
        return $this->departure_time->isToday();
    }

    /**
     * Label relatif waktu (Hari ini, Besok, atau tanggal)
     */
    public function getRelativeDateLabel(): string
    {
        if ($this->isToday()) return 'Hari ini';
        if ($this->isTomorrow()) return 'Besok';
        return $this->getDepartureDateLabel();
    }
}