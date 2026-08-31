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

    /**
     * Notify the entity owner (manager) when a new booking arrives.
     * transport_ticket has no manager owner -> skipped.
     */
    public static function createNewBookingForManager(Booking $booking): ?self
    {
        $managerId = match ($booking->booking_type) {
            'hotel'              => $booking->hotelBooking?->hotel?->manager_id,
            'transportation'     => $booking->transportationBooking?->transportation?->manager_id,
            'travel_package'     => $booking->packageBooking?->travelPackage?->manager_id,
            'destination_ticket' => $booking->destinationTicketBooking?->destination?->manager_id,
            default              => null,
        };

        if (! $managerId) {
            return null;
        }

        return self::create([
            'user_id' => $managerId,
            'title' => 'Booking Baru',
            'message' => "Booking baru {$booking->booking_type} #{$booking->booking_number}. Total: Rp " . number_format($booking->total_price, 0, ',', '.'),
            'type' => 'new_booking',
            'notifiable_type' => Booking::class,
            'notifiable_id' => $booking->id,
            'action' => [
                'label' => 'Lihat Booking',
                'url' => '/dashboard/manager/bookings',
            ],
        ]);
    }

    /**
     * Notify the UMKM owner when a new order arrives.
     */
    public static function createOrderReceived(Order $order): ?self
    {
        $ownerId = $order->umkm?->user_id;

        if (! $ownerId) {
            return null;
        }

        return self::create([
            'user_id' => $ownerId,
            'title' => 'Pesanan Baru',
            'message' => "Pesanan baru #{$order->order_number}. Total: Rp " . number_format($order->total_price, 0, ',', '.'),
            'type' => 'new_order',
            'notifiable_type' => Order::class,
            'notifiable_id' => $order->id,
            'action' => [
                'label' => 'Lihat Pesanan',
                'url' => '/dashboard/umkm/orders',
            ],
        ]);
    }

    /**
     * Notify the entity owner (manager / UMKM) when a new review arrives.
     */
    public static function createNewReview(Review $review): ?self
    {
        $review->loadMissing('reviewable');
        $reviewable = $review->reviewable;

        $ownerId = match ($reviewable::class) {
            Destination::class => $reviewable->manager_id,
            Umkm::class       => $reviewable->user_id,
            Product::class    => $reviewable->umkm?->user_id,
            default           => null,
        };

        if (! $ownerId || $ownerId === $review->user_id) {
            return null;
        }

        return self::create([
            'user_id' => $ownerId,
            'title' => 'Review Baru',
            'message' => "Ada review baru (" . str_repeat('★', $review->rating) . ") untuk {$reviewable->name}.",
            'type' => 'new_review',
            'notifiable_type' => Review::class,
            'notifiable_id' => $review->id,
            'action' => [
                'label' => 'Lihat Review',
                'url' => '/dashboard/reviews',
            ],
        ]);
    }

    /**
     * Notify the review author when the owner responds to their review.
     */
    public static function createReviewResponse(Review $review): ?self
    {
        if (! $review->response_by) {
            return null;
        }

        return self::create([
            'user_id' => $review->user_id,
            'title' => 'Review Anda Dibalas',
            'message' => 'Pengelola telah menanggapi review Anda.',
            'type' => 'review_response',
            'notifiable_type' => Review::class,
            'notifiable_id' => $review->id,
            'action' => [
                'label' => 'Lihat Review',
                'url' => '/notifications',
            ],
        ]);
    }

    /**
     * Notify user of a daily claim reward (optional voucher on day 7).
     */
    public static function createDailyReward(User $user, float $coins, ?string $voucherCode = null): self
    {
        $message = "Bonus klaim harian: +{$coins} EJTCoin";
        if ($voucherCode) {
            $message .= ' + voucher gratis ' . $voucherCode;
        }

        return self::create([
            'user_id' => $user->id,
            'title' => 'Bonus Harian',
            'message' => $message,
            'type' => 'daily_reward',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'action' => [
                'label' => 'Lihat Loyalty',
                'url' => '/loyalty',
            ],
        ]);
    }

    /**
     * Notify the referral owner when a new user registers using their code.
     */
    public static function createReferralRegistered(User $referrer, User $referee): ?self
    {
        if ($referrer->id === $referee->id) {
            return null;
        }

        return self::create([
            'user_id' => $referrer->id,
            'title' => 'Referal Baru',
            'message' => "{$referee->name} bergabung menggunakan kode referal kamu. Bonus +500 EJTCoin kamu aktif saat mereka melakukan booking pertama.",
            'type' => 'referral_registered',
            'notifiable_type' => User::class,
            'notifiable_id' => $referee->id,
            'action' => [
                'label' => 'Lihat Loyalty',
                'url' => '/loyalty',
            ],
        ]);
    }

    /**
     * Notify the user that a wishlisted item's price dropped >= 5%.
     */
    public static function createPriceDrop(WishlistItem $item, float $oldPrice, float $newPrice): ?self
    {
        $resolved = app(\App\Services\CatalogPriceResolver::class)->resolve(
            \App\Services\CatalogPriceResolver::typeFromClass($item->wishlistable_type) ?? 'destination',
            $item->wishlistable_id
        );

        $name = $resolved['name'] ?? $item->wishlistable?->name ?? 'Item';

        return self::create([
            'user_id' => $item->collection?->user_id,
            'title' => 'Harga Turun',
            'message' => "Harga \"{$name}\" turun dari Rp " . number_format($oldPrice, 0, ',', '.') . ' menjadi Rp ' . number_format($newPrice, 0, ',', '.'),
            'type' => 'price_drop',
            'notifiable_type' => WishlistItem::class,
            'notifiable_id' => $item->id,
            'action' => [
                'label' => 'Lihat Wishlist',
                'url' => '/wishlist',
            ],
            'data' => [
                'wishlist_item_id' => $item->id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
            ],
        ]);
    }

    /**
     * Notify the user that a wishlisted item reached their target price.
     */
    public static function createPriceTarget(WishlistItem $item, float $price): ?self
    {
        $resolved = app(\App\Services\CatalogPriceResolver::class)->resolve(
            \App\Services\CatalogPriceResolver::typeFromClass($item->wishlistable_type) ?? 'destination',
            $item->wishlistable_id
        );

        $name = $resolved['name'] ?? $item->wishlistable?->name ?? 'Item';

        return self::create([
            'user_id' => $item->collection?->user_id,
            'title' => 'Target Harga Tercapai',
            'message' => "\"{$name}\" kini menyentuh targetmu: Rp " . number_format($price, 0, ',', '.'),
            'type' => 'price_target',
            'notifiable_type' => WishlistItem::class,
            'notifiable_id' => $item->id,
            'action' => [
                'label' => 'Lihat Wishlist',
                'url' => '/wishlist',
            ],
            'data' => [
                'wishlist_item_id' => $item->id,
                'new_price' => $price,
            ],
        ]);
    }
}
