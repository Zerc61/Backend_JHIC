<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryItem extends Model
{
    use HasFactory;

    public const SLOTS = ['morning', 'afternoon', 'evening'];

    public const TYPES = ['hotel', 'destination', 'umkm', 'transport', 'custom'];

    protected $fillable = [
        'day_id',
        'slot',
        'type',
        'reference_id',
        'custom_name',
        'custom_note',
        'estimated_cost',
        'duration_minutes',
        'sort_order',
        'lat',
        'lng',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'duration_minutes' => 'integer',
            'sort_order' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(ItineraryDay::class, 'day_id');
    }

    public function reference()
    {
        return match ($this->type) {
            'hotel' => $this->belongsTo(Hotel::class, 'reference_id'),
            'destination' => $this->belongsTo(Destination::class, 'reference_id'),
            'umkm' => $this->belongsTo(Product::class, 'reference_id'),
            'transport' => $this->belongsTo(TransportTicket::class, 'reference_id'),
            default => null,
        };
    }

    public function slotLabel(string $slot): string
    {
        return match ($slot) {
            'morning' => 'Pagi',
            'afternoon' => 'Siang',
            'evening' => 'Malam',
            default => '—',
        };
    }
}