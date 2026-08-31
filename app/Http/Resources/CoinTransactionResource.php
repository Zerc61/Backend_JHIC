<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoinTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'amount' => (float) $this->amount,
            'amount_formatted' => \App\Helpers\GeneralHelper::formatCoin((float) $this->amount),
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_expired' => (bool) $this->is_expired,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}