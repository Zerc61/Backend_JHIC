<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'total_price' => (float) $this->total_price,
            'total_price_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->total_price),
            'status' => $this->status->value,
            'payment_method' => $this->payment_method->value,
            'coin_amount' => (float) $this->coin_amount,
            'coin_amount_formatted' => \App\Helpers\GeneralHelper::formatCoin((float) $this->coin_amount),
            'notes' => $this->notes,
            'qr_code' => $this->qr_code,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'picked_up_at' => $this->picked_up_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'umkm' => [
                'id' => $this->umkm->id,
                'name' => $this->umkm->name,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}