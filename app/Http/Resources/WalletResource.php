<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance' => (float) $this->balance,
            'balance_formatted' => \App\Helpers\GeneralHelper::formatCoin((float) $this->balance),
            'balance_in_rupiah' => (float) $this->balance * 2000,
            'balance_in_rupiah_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->balance * 2000),
        ];
    }
}