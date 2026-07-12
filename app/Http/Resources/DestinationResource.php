<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DestinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // FIX: Handle URL dengan benar
        $mainImage = $this->main_image;
        if ($mainImage) {
            // Kalau sudah URL lengkap (http/https), pakai langsung
            if (!str_starts_with($mainImage, 'http')) {
                $mainImage = url("storage/{$mainImage}");
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'main_image' => $mainImage,
            'ticket_price' => (float) $this->ticket_price,
            'ticket_price_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->ticket_price),
            'average_rating' => round($this->average_rating, 1),
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'status' => $this->status->value,
        ];
    }
}