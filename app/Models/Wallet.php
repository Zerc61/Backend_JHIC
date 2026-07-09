<?php

namespace App\Models;

use App\Enums\CoinTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:4',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coinTransactions()
    {
        return $this->hasMany(CoinTransaction::class);
    }

    /**
     * Tambah saldo coin (top up, refund)
     */
    public function credit(float $amount, string $description, ?Model $reference = null): CoinTransaction
    {
        return DB::transaction(function () use ($amount, $description, $reference) {
            $before = $this->balance;
            $this->increment('balance', $amount);
            $after = $this->fresh()->balance;

            return $this->coinTransactions()->create([
                'type' => CoinTransactionType::CREDIT,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
            ]);
        });
    }

    /**
     * Kurangi saldo coin (bayar order)
     */
    public function debit(float $amount, string $description, ?Model $reference = null): CoinTransaction
    {
        return DB::transaction(function () use ($amount, $description, $reference) {
            if ($this->balance < $amount) {
                throw new \Exception('Saldo coin tidak mencukupi.');
            }

            $before = $this->balance;
            $this->decrement('balance', $amount);
            $after = $this->fresh()->balance;

            return $this->coinTransactions()->create([
                'type' => CoinTransactionType::DEBIT,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
            ]);
        });
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}