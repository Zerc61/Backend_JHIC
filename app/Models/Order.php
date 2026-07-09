<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'umkm_id',
        'total_price',
        'status',
        'qr_code',
        'notes',
        'payment_method',
        'coin_amount',
        'coin_to_rupiah_rate',
        'rupiah_equivalent',
        'picked_up_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'coin_amount' => 'decimal:4',
            'coin_to_rupiah_rate' => 'decimal:2',
            'rupiah_equivalent' => 'decimal:2',
            'picked_up_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = 'NS-' . strtoupper(Str::random(10));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function umkm()
    {
        return $this->belongsTo(Umkm::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coinTransaction()
    {
        return $this->morphOne(CoinTransaction::class, 'reference');
    }

    public function isPaidWithCoin(): bool
    {
        return $this->payment_method === PaymentMethod::COIN;
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [OrderStatus::PAID, OrderStatus::PREPARING, OrderStatus::READY, OrderStatus::PICKED_UP]);
    }

    public function isPickedUp(): bool
    {
        return $this->status === OrderStatus::PICKED_UP;
    }
}