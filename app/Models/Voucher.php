<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Voucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_purchase',
        'total_quota',
        'per_user_limit',
        'valid_from',
        'valid_until',
        'is_active',
        'applicable_to',
        'applicable_items',
        'conditions',
        'is_free',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'applicable_items' => 'array',
        'conditions' => 'array',
        'is_free' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(VoucherClaim::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Check if voucher is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($now->isBefore($this->valid_from) || $now->isAfter($this->valid_until)) {
            return false;
        }

        if ($this->total_quota && $this->used_count >= $this->total_quota) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can use this voucher
     */
    public function canUseByUser($userId): array
    {
        if (!$this->isValid()) {
            return ['valid' => false, 'message' => 'Voucher tidak valid atau sudah expired'];
        }

        $userUsageCount = $this->usages()
            ->where('user_id', $userId)
            ->where('status', 'applied')
            ->count();

        if ($userUsageCount >= $this->per_user_limit) {
            return ['valid' => false, 'message' => "Anda sudah menggunakan voucher ini {$this->per_user_limit}x"];
        }

        return ['valid' => true];
    }

    /**
     * Check if voucher applies to item
     */
    public function appliesToItem($itemType, $itemId): bool
    {
        if ($this->applicable_to === 'all') {
            return true;
        }

        if ($this->applicable_to === $itemType && is_array($this->applicable_items)) {
            return in_array($itemId, $this->applicable_items);
        }

        return false;
    }

    /**
     * Calculate discount for amount
     */
    public function calculateDiscount($amount): array
    {
        $discount = 0;

        if ($amount < $this->min_purchase) {
            return [
                'discount' => 0,
                'final_amount' => $amount,
                'message' => "Minimum pembelian Rp " . number_format($this->min_purchase, 0, ',', '.'),
            ];
        }

        if ($this->discount_type === 'percentage') {
            $discount = ($amount * $this->discount_value) / 100;
            if ($this->max_discount) {
                $discount = min($discount, $this->max_discount);
            }
        } else {
            $discount = $this->discount_value;
        }

        $finalAmount = max(0, $amount - $discount);

        return [
            'discount' => $discount,
            'final_amount' => $finalAmount,
            'valid' => true,
        ];
    }

    /**
     * Apply voucher to transaction
     */
    public function applyToOrder(Order $order, $userId)
    {
        $validation = $this->canUseByUser($userId);
        if (!$validation['valid']) {
            return ['valid' => false, 'message' => $validation['message']];
        }

        $discountCalc = $this->calculateDiscount($order->total_price);
        if (!$discountCalc['valid'] ?? true) {
            return $discountCalc;
        }

        // Create usage record
        $usage = VoucherUsage::create([
            'voucher_id' => $this->id,
            'user_id' => $userId,
            'usable_type' => Order::class,
            'usable_id' => $order->id,
            'discount_amount' => $discountCalc['discount'],
            'original_amount' => $order->total_price,
            'final_amount' => $discountCalc['final_amount'],
            'used_at' => now(),
        ]);

        // Increment voucher usage count
        $this->increment('used_count');

        return [
            'valid' => true,
            'discount' => $discountCalc['discount'],
            'final_amount' => $discountCalc['final_amount'],
            'usage_id' => $usage->id,
        ];
    }

    /**
     * Apply voucher to booking
     */
    public function applyToBooking(Booking $booking, $userId)
    {
        $validation = $this->canUseByUser($userId);
        if (!$validation['valid']) {
            return ['valid' => false, 'message' => $validation['message']];
        }

        $discountCalc = $this->calculateDiscount($booking->total_price);
        if (!$discountCalc['valid'] ?? true) {
            return $discountCalc;
        }

        // Create usage record
        $usage = VoucherUsage::create([
            'voucher_id' => $this->id,
            'user_id' => $userId,
            'usable_type' => Booking::class,
            'usable_id' => $booking->id,
            'discount_amount' => $discountCalc['discount'],
            'original_amount' => $booking->total_price,
            'final_amount' => $discountCalc['final_amount'],
            'used_at' => now(),
        ]);

        // Increment voucher usage count
        $this->increment('used_count');

        return [
            'valid' => true,
            'discount' => $discountCalc['discount'],
            'final_amount' => $discountCalc['final_amount'],
            'usage_id' => $usage->id,
        ];
    }

    /**
     * Reverse voucher usage
     */
    public function reverseUsage($usageId)
    {
        $usage = $this->usages()->find($usageId);
        if (!$usage) {
            return false;
        }

        $usage->update(['status' => 'cancelled']);
        $this->decrement('used_count');

        return true;
    }
}
