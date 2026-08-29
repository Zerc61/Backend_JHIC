<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->formatted_number,
            'user_id' => $this->user_id,
            'transaction_type' => $this->transaction_type,
            'transaction_type_label' => match ($this->transaction_type) {
                'order' => 'Pesanan UMKM',
                'booking' => 'Booking Perjalanan',
                'top_up' => 'Top Up EJTCoin',
                default => $this->transaction_type,
            },
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'total_amount' => (float) $this->total_amount,
            'total_formatted' => $this->formatted_amount,
            'payment_method' => $this->payment_method,
            'payment_method_label' => match ($this->payment_method) {
                'coin' => 'EJTCoin',
                'cash_on_pickup' => 'Bayar Saat Ambil',
                'card' => 'Kartu Kredit',
                'transfer' => 'Transfer Bank',
                default => $this->payment_method,
            },
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->status_badge,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'items' => $this->items_json,
            'notes' => $this->notes,
            'invoiceable_type' => $this->invoiceable_type,
            'invoiceable_id' => $this->invoiceable_id,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
