<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyReward extends Model
{
    protected $fillable = [
        'user_id',
        'reward_key',
        'coin_transaction_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coinTransaction(): BelongsTo
    {
        return $this->belongsTo(CoinTransaction::class);
    }
}