<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'notifiable_type',
        'notifiable_id',
        'is_read',
        'read_at',
        'channel',
        'sent',
        'sent_at',
        'data',
        'action',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent' => 'boolean',
        'data' => 'array',
        'action' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
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

    public function notifiable(): MorphTo
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

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->latest('created_at');
    }

    /**
     * Mark as read
     */
    public function markAsRead(): self
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as unread
     */
    public function markAsUnread(): self
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);

        return $this;
    }

    /**
     * Mark as sent
     */
    public function markAsSent(): self
    {
        $this->update([
            'sent' => true,
            'sent_at' => now(),
        ]);

        return $this;
    }

    /**
     * Create notification for booking confirmation
     */
    public static function createBookingConfirmation(Booking $booking): self
    {
        return self::create([
            'user_id' => $booking->user_id,
            'title' => 'Booking Dikonfirmasi',
            'message' => "Booking #{$booking->booking_number} telah dikonfirmasi. Total: Rp " . number_format($booking->total_price, 0, ',', '.'),
            'type' => 'booking_confirmed',
            'notifiable_type' => Booking::class,
            'notifiable_id' => $booking->id,
            'action' => [
                'label' => 'Lihat Booking',
                'url' => "/bookings/{$booking->booking_number}",
            ],
        ]);
    }

    /**
     * Create notification for order status change
     */
    public static function createOrderStatusUpdate(Order $order, string $status): self
    {
        $statusLabel = match ($status) {
            'paid' => 'Dibayar',
            'pending' => 'Menunggu',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => $status,
        };

        return self::create([
            'user_id' => $order->user_id,
            'title' => "Pesanan {$statusLabel}",
            'message' => "Pesanan #{$order->order_number} status berubah menjadi {$statusLabel}.",
            'type' => 'order_status',
            'notifiable_type' => Order::class,
            'notifiable_id' => $order->id,
            'action' => [
                'label' => 'Lihat Pesanan',
                'url' => "/orders/{$order->id}",
            ],
        ]);
    }

    /**
     * Create notification for payment received
     */
    public static function createPaymentReceived(TopUpTransaction $topUp): self
    {
        return self::create([
            'user_id' => $topUp->user_id,
            'title' => 'Pembayaran Diterima',
            'message' => "Top-up Rp " . number_format($topUp->amount_rupiah, 0, ',', '.') . " berhasil. {$topUp->coins_received} EJTCoin telah ditambahkan ke wallet Anda.",
            'type' => 'payment_received',
            'notifiable_type' => TopUpTransaction::class,
            'notifiable_id' => $topUp->id,
            'action' => [
                'label' => 'Lihat Wallet',
                'url' => '/wallet',
            ],
        ]);
    }

    /**
     * Create notification for refund processed
     */
    public static function createRefundProcessed($reference, $amount, $reason): self
    {
        $user = $reference instanceof Order ? $reference->user : $reference->user;

        return self::create([
            'user_id' => $user->id,
            'title' => 'Refund Diproses',
            'message' => "Refund sebesar Rp " . number_format($amount, 0, ',', '.') . " telah diproses. Alasan: {$reason}",
            'type' => 'refund_processed',
            'notifiable_type' => get_class($reference),
            'notifiable_id' => $reference->id,
            'data' => [
                'amount' => $amount,
                'reason' => $reason,
            ],
        ]);
    }

    /**
     * Create system alert
     */
    public static function createSystemAlert($userId, $title, $message): self
    {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => 'system_alert',
        ]);
    }
}
