<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DestinationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Helper untuk handle URL
        $formatImage = function ($image) {
            if (!$image) return null;
            if (str_starts_with($image, 'http')) return $image;
            return url("storage/{$image}");
        };

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'open_hour' => $this->open_hour,
            'close_hour' => $this->close_hour,
            'ticket_price' => (float) $this->ticket_price,
            'ticket_price_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->ticket_price),
            'phone' => $this->phone,
            'website' => $this->website,
            'average_rating' => round($this->average_rating, 1),
            'status' => $this->status->value,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'galleries' => $this->whenLoaded('galleries', fn() => $this->galleries->map(fn($g) => [
                'id' => $g->id,
                'image' => $formatImage($g->image),
                'caption' => $g->caption,
            ])->sortBy('sort_order')->values()),
            'facilities' => $this->whenLoaded('facilities', fn() => $this->facilities->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'icon' => $f->icon,
            ])->values()),
            'umkms_count' => $this->whenLoaded('umkms', fn() => $this->umkms->count()),
            'events_count' => $this->whenLoaded('events', fn() => $this->events->count()),
        ];
    }
}