<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'max_discount' => (float) $this->max_discount,
            'min_purchase' => (float) $this->min_purchase,
            'valid_from' => $this->valid_from?->format('Y-m-d H:i:s'),
            'valid_until' => $this->valid_until?->format('Y-m-d H:i:s'),
            'conditions' => $this->conditions,
            'is_free' => (bool) $this->is_free,
            'quota_remaining' => $this->total_quota ? $this->total_quota - $this->used_count : null,
        ];
    }
}