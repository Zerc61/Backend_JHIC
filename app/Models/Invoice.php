<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'invoiceable_type',
        'invoiceable_id',
        'transaction_type',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'payment_method',
        'payment_status',
        'paid_at',
        'notes',
        'items_json',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'items_json' => 'array',
        'paid_at' => 'datetime',
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

    public function invoiceable(): MorphTo
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
        return $query->where('payment_status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->latest('created_at');
    }

    /**
     * Accessors
     */
    public function getFormattedNumberAttribute(): string
    {
        return "INV-{$this->invoice_number}";
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Dibayar',
            'pending' => 'Menunggu',
            'failed' => 'Gagal',
            'refunded' => 'Dikembalikan',
            default => $this->payment_status,
        };
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = date('Ymd');
        $lastInvoice = self::where('invoice_number', 'like', "$prefix%")
            ->latest('id')
            ->first();

        $sequence = $lastInvoice
            ? intval(substr($lastInvoice->invoice_number, -4)) + 1
            : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create invoice from order
     */
    public static function createFromOrder(Order $order): self
    {
        $invoice = new self();
        $invoice->invoice_number = self::generateInvoiceNumber();
        $invoice->user_id = $order->user_id;
        $invoice->invoiceable_type = Order::class;
        $invoice->invoiceable_id = $order->id;
        $invoice->transaction_type = 'order';
        $invoice->subtotal = $order->total_price;
        $invoice->tax = 0;
        $invoice->discount = 0;
        $invoice->total_amount = $order->total_price;
        $invoice->payment_method = $order->payment_method;
        $invoice->payment_status = $order->status === 'paid' ? 'paid' : 'pending';
        $invoice->paid_at = $order->paid_at;
        $invoice->items_json = $order->items->map(fn($item) => [
            'product_name' => $item->product_name,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'subtotal' => $item->subtotal,
        ])->toArray();
        $invoice->save();

        return $invoice;
    }

    /**
     * Create invoice from booking
     */
    public static function createFromBooking(Booking $booking): self
    {
        $invoice = new self();
        $invoice->invoice_number = self::generateInvoiceNumber();
        $invoice->user_id = $booking->user_id;
        $invoice->invoiceable_type = Booking::class;
        $invoice->invoiceable_id = $booking->id;
        $invoice->transaction_type = 'booking';
        $invoice->subtotal = $booking->total_price;
        $invoice->tax = 0;
        $invoice->discount = 0;
        $invoice->total_amount = $booking->total_price;
        $invoice->payment_method = 'coin';
        $invoice->payment_status = $booking->status === 'paid' ? 'paid' : 'pending';
        $invoice->paid_at = $booking->paid_at;
        $invoice->items_json = [
            'booking_type' => $booking->booking_type,
            'booking_number' => $booking->booking_number,
        ];
        $invoice->save();

        return $invoice;
    }

    /**
     * Create invoice from top-up
     */
    public static function createFromTopUp(TopUpTransaction $topUp): self
    {
        $invoice = new self();
        $invoice->invoice_number = self::generateInvoiceNumber();
        $invoice->user_id = $topUp->user_id;
        $invoice->invoiceable_type = TopUpTransaction::class;
        $invoice->invoiceable_id = $topUp->id;
        $invoice->transaction_type = 'top_up';
        $invoice->subtotal = $topUp->amount_rupiah;
        $invoice->tax = 0;
        $invoice->discount = 0;
        $invoice->total_amount = $topUp->amount_rupiah;
        $invoice->payment_method = $topUp->payment_type ?? 'transfer';
        $invoice->payment_status = $topUp->status->value === 'success' ? 'paid' : 'pending';
        $invoice->paid_at = $topUp->paid_at;
        $invoice->items_json = [
            'coins_received' => $topUp->coins_received,
            'rate' => $topUp->rate_per_coin,
        ];
        $invoice->save();

        return $invoice;
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(): self
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as refunded
     */
    public function markAsRefunded(): self
    {
        $this->update([
            'payment_status' => 'refunded',
        ]);

        return $this;
    }
}
