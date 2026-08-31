<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherClaim extends Model
{
    protected $fillable = [
        'user_id',
        'voucher_id',
        'source',
        'status',
        'claimed_at',
        'used_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}