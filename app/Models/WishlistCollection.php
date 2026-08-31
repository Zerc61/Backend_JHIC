<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WishlistCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_default',
        'is_public',
        'share_token',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class, 'collection_id');
    }

    public function ensureShareToken(): string
    {
        if (empty($this->share_token)) {
            do {
                $token = 'wl_' . Str::random(40);
            } while (self::where('share_token', $token)->exists());

            $this->forceFill(['share_token' => $token])->save();
        }

        return $this->share_token;
    }

    public function isOwner(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}