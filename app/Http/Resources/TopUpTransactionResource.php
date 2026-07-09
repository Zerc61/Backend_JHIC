<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopUpTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->midtrans_order_id,
            'amount_rupiah' => (float) $this->amount_rupiah,
            'amount_rupiah_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->amount_rupiah),
            'rate_per_coin' => (float) $this->rate_per_coin,
            'coins_received' => (float) $this->coins_received,
            'coins_received_formatted' => \App\Helpers\GeneralHelper::formatCoin((float) $this->coins_received),
            'payment_type' => $this->payment_type,
            'va_number' => $this->va_number,
            'status' => $this->status->value,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'expired_at' => $this->expired_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}