<?php

namespace App\Models;

use App\Enums\TopUpStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TopUpTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount_rupiah',
        'rate_per_coin',
        'coins_received',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'payment_type',
        'va_number',
        'status',
        'paid_at',
        'expired_at',
        'midtrans_raw_response',
    ];

    protected function casts(): array
    {
        return [
            'amount_rupiah' => 'decimal:2',
            'rate_per_coin' => 'decimal:2',
            'coins_received' => 'decimal:4',
            'status' => TopUpStatus::class,
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
            'midtrans_raw_response' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TopUpTransaction $topUp) {
            if (empty($topUp->midtrans_order_id)) {
                $topUp->midtrans_order_id = 'TOPUP-' . strtoupper(Str::random(12));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coinTransaction()
    {
        return $this->morphOne(CoinTransaction::class, 'reference');
    }

    public function isSuccessful(): bool
    {
        return $this->status === TopUpStatus::SUCCESS;
    }

    public function isPending(): bool
    {
        return $this->status === TopUpStatus::PENDING;
    }
}