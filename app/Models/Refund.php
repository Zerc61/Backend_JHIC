<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Refund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'refund_number',
        'user_id',
        'invoice_id',
        'refundable_type',
        'refundable_id',
        'reason',
        'description',
        'refund_amount',
        'refund_method',
        'status',
        'approved_at',
        'processed_at',
        'completed_at',
        'approved_by',
        'approval_notes',
        'transaction_reference',
        'metadata',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function refundable(): MorphTo
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

    public function scopeApproved($query)
    {
        return $query->where('status', '!=', 'rejected')->where('status', '!=', 'pending');
    }

    public function scopeRecent($query)
    {
        return $query->latest('created_at');
    }

    /**
     * Generate refund number
     */
    public static function generateRefundNumber(): string
    {
        $prefix = 'RF' . date('Ymd');
        $lastRefund = self::where('refund_number', 'like', "$prefix%")
            ->latest('id')
            ->first();

        $sequence = $lastRefund
            ? intval(substr($lastRefund->refund_number, -4)) + 1
            : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create refund request for order
     */
    public static function createForOrder(Order $order, string $reason, string $description): self
    {
        $refund = new self();
        $refund->refund_number = self::generateRefundNumber();
        $refund->user_id = $order->user_id;
        $refund->refundable_type = Order::class;
        $refund->refundable_id = $order->id;
        $refund->reason = $reason;
        $refund->description = $description;
        $refund->refund_amount = $order->total_price;
        $refund->refund_method = $order->payment_method === 'coin' ? 'coin_wallet' : 'original_payment';
        $refund->status = 'pending';
        $refund->save();

        // Create invoice reference if exists
        $invoice = Invoice::where('invoiceable_type', Order::class)
            ->where('invoiceable_id', $order->id)
            ->first();
        if ($invoice) {
            $refund->invoice_id = $invoice->id;
            $refund->save();
        }

        return $refund;
    }

    /**
     * Create refund request for booking
     */
    public static function createForBooking(Booking $booking, string $reason, string $description): self
    {
        $refund = new self();
        $refund->refund_number = self::generateRefundNumber();
        $refund->user_id = $booking->user_id;
        $refund->refundable_type = Booking::class;
        $refund->refundable_id = $booking->id;
        $refund->reason = $reason;
        $refund->description = $description;
        $refund->refund_amount = $booking->total_price;
        $refund->refund_method = 'coin_wallet';
        $refund->status = 'pending';
        $refund->save();

        // Create invoice reference if exists
        $invoice = Invoice::where('invoiceable_type', Booking::class)
            ->where('invoiceable_id', $booking->id)
            ->first();
        if ($invoice) {
            $refund->invoice_id = $invoice->id;
            $refund->save();
        }

        return $refund;
    }

    /**
     * Approve refund
     */
    public function approve(User $approver, string $notes = ''): self
    {
        $this->update([
            'status' => 'processing',
            'approved_at' => now(),
            'approved_by' => $approver->id,
            'approval_notes' => $notes,
        ]);

        // Create notification
        Notification::create([
            'user_id' => $this->user_id,
            'title' => 'Refund Disetujui',
            'message' => "Refund Anda sebesar Rp " . number_format($this->refund_amount, 0, ',', '.') . " telah disetujui dan sedang diproses.",
            'type' => 'refund_approved',
            'notifiable_type' => self::class,
            'notifiable_id' => $this->id,
        ]);

        return $this;
    }

    /**
     * Reject refund
     */
    public function reject(User $rejector, string $reason): self
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $rejector->id,
            'approval_notes' => $reason,
        ]);

        // Create notification
        Notification::create([
            'user_id' => $this->user_id,
            'title' => 'Refund Ditolak',
            'message' => "Refund Anda ditolak. Alasan: {$reason}",
            'type' => 'refund_rejected',
            'notifiable_type' => self::class,
            'notifiable_id' => $this->id,
        ]);

        return $this;
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(string $transactionReference = null): self
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'completed_at' => now(),
            'transaction_reference' => $transactionReference,
        ]);

        // Process refund to wallet if coin method
        if ($this->refund_method === 'coin_wallet') {
            $this->user->wallet->credit(
                (float) ($this->refund_amount / 2000), // Convert to coins
                "Refund: {$this->reason}",
                $this
            );
        }

        // Create notification
        Notification::create([
            'user_id' => $this->user_id,
            'title' => 'Refund Selesai',
            'message' => "Refund Anda sebesar Rp " . number_format($this->refund_amount, 0, ',', '.') . " telah selesai diproses.",
            'type' => 'refund_completed',
            'notifiable_type' => self::class,
            'notifiable_id' => $this->id,
        ]);

        return $this;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->refund_amount, 0, ',', '.');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'processing' => 'Diproses',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            default => $this->status,
        };
    }
}
