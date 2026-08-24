<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_number',
        'user_id',
        'payable_type',
        'payable_id',
        'amount',
        'currency',
        'payment_method',
        'description',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_response',
        'status',
        'expires_at',
        'paid_at',
        'failed_at',
        'retry_count',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'midtrans_response' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', 'pending');
    }

    public function scopeRecent($query)
    {
        return $query->latest('created_at');
    }

    /**
     * Generate transaction number
     */
    public static function generateTransactionNumber(): string
    {
        $prefix = 'PAY' . date('Ymd');
        $lastTransaction = self::where('transaction_number', 'like', "$prefix%")
            ->latest('id')
            ->first();

        $sequence = $lastTransaction
            ? intval(substr($lastTransaction->transaction_number, -6)) + 1
            : 1;

        return $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create payment for order
     */
    public static function createForOrder(Order $order, string $paymentMethod = 'card'): self
    {
        $transaction = new self();
        $transaction->transaction_number = self::generateTransactionNumber();
        $transaction->user_id = $order->user_id;
        $transaction->payable_type = Order::class;
        $transaction->payable_id = $order->id;
        $transaction->amount = $order->total_amount ?? $order->total_price;
        $transaction->currency = 'IDR';
        $transaction->payment_method = $paymentMethod;
        $transaction->description = "Payment for order #{$order->order_number}";
        $transaction->midtrans_order_id = self::generateTransactionNumber();
        $transaction->status = 'pending';
        $transaction->expires_at = now()->addMinutes(30); // 30 min expiration
        $transaction->save();

        return $transaction;
    }

    /**
     * Create payment for booking
     */
    public static function createForBooking(Booking $booking, string $paymentMethod = 'card'): self
    {
        $transaction = new self();
        $transaction->transaction_number = self::generateTransactionNumber();
        $transaction->user_id = $booking->user_id;
        $transaction->payable_type = Booking::class;
        $transaction->payable_id = $booking->id;
        $transaction->amount = $booking->total_amount ?? $booking->total_price;
        $transaction->currency = 'IDR';
        $transaction->payment_method = $paymentMethod;
        $transaction->description = "Payment for booking #{$booking->booking_number}";
        $transaction->midtrans_order_id = self::generateTransactionNumber();
        $transaction->status = 'pending';
        $transaction->expires_at = now()->addMinutes(30); // 30 min expiration
        $transaction->save();

        return $transaction;
    }

    /**
     * Check if payment is expired
     */
    public function isExpired(): bool
    {
        return $this->status === 'pending' && now()->isAfter($this->expires_at);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): self
    {
        $this->update(['status' => 'processing']);
        return $this;
    }

    /**
     * Mark as success
     */
    public function markAsSuccess(array $midtransResponse = []): self
    {
        $this->update([
            'status' => 'success',
            'paid_at' => now(),
            'midtrans_transaction_id' => $midtransResponse['transaction_id'] ?? null,
            'midtrans_response' => $midtransResponse,
        ]);

        // Update payable status
        if ($this->payable_type === Order::class) {
            $this->payable->update(['status' => 'paid', 'paid_at' => now()]);
        } elseif ($this->payable_type === Booking::class) {
            $this->payable->update(['status' => 'paid', 'paid_at' => now()]);
        }

        // Create invoice
        if ($this->payable_type === Order::class) {
            Invoice::createFromOrder($this->payable);
        } elseif ($this->payable_type === Booking::class) {
            Invoice::createFromBooking($this->payable);
        }

        // Create notification
        Notification::create([
            'user_id' => $this->user_id,
            'title' => 'Pembayaran Berhasil',
            'message' => "Pembayaran Rp " . number_format($this->amount, 0, ',', '.') . " berhasil diproses.",
            'type' => 'payment_received',
            'notifiable_type' => self::class,
            'notifiable_id' => $this->id,
        ]);

        return $this;
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage = ''): self
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);

        // Create notification
        Notification::create([
            'user_id' => $this->user_id,
            'title' => 'Pembayaran Gagal',
            'message' => "Pembayaran Rp " . number_format($this->amount, 0, ',', '.') . " gagal diproses. Silakan coba lagi.",
            'type' => 'payment_failed',
            'notifiable_type' => self::class,
            'notifiable_id' => $this->id,
        ]);

        return $this;
    }

    /**
     * Mark as expired
     */
    public function markAsExpired(): self
    {
        $this->update(['status' => 'expired']);
        
        // Release hold/booking if exists
        if ($this->payable_type === Booking::class) {
            BookingHold::where('booking_id', $this->payable_id)->delete();
        }

        return $this;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Pembayaran',
            'processing' => 'Diproses',
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kedaluwarsa',
            default => $this->status,
        };
    }
}
