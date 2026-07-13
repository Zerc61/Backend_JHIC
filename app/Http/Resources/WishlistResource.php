<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'created_at' => $this->created_at?->format('d M Y H:i'),
            'destination' => [
                'id'                      => $this->wishlistable?->id,
                'name'                    => $this->wishlistable?->name,
                'slug'                    => $this->wishlistable?->slug,
                'main_image'              => $this->wishlistable?->main_image,
                'address'                 => $this->wishlistable?->address,
                'ticket_price_formatted'  => $this->wishlistable?->ticket_price_formatted,
                'category'                => $this->wishlistable?->category ? [
                    'id'   => $this->wishlistable->category->id,
                    'name' => $this->wishlistable->category->name,
                    'icon' => $this->wishlistable->category->icon,
                ] : null,
            ],
        ];
    }
}