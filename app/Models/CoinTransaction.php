<?php

namespace App\Models;

use App\Enums\CoinTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'balance_before' => 'decimal:4',
            'balance_after' => 'decimal:4',
            'type' => CoinTransactionType::class,
        ];
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function isCredit(): bool
    {
        return $this->type === CoinTransactionType::CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->type === CoinTransactionType::DEBIT;
    }
}