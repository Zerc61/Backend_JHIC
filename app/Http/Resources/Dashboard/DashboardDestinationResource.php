<?php

namespace App\Http\Resources\Dashboard;

use App\Helpers\GeneralHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardDestinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Ambil gambar utama: gallery pertama, atau null
        $mainImage = $this->whenLoaded('galleries', fn () => $this->galleries->first()?->image);

        // ✅ FIX: Parse string ke Carbon dulu sebelum format
        $openHour = $this->open_hour ? Carbon::parse($this->open_hour)->format('H:i') : null;
        $closeHour = $this->close_hour ? Carbon::parse($this->close_hour)->format('H:i') : null;
        $operatingHours = ($openHour && $closeHour) ? "{$openHour} - {$closeHour}" : null;

        // Facilities dari pivot (hanya name + icon)
        $facilities = $this->whenLoaded('facilities', fn () => $this->facilities->map(fn ($f) => [
            'name' => $f->name,
            'icon' => $f->icon,
        ]));

        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'slug'                    => $this->slug,
            'main_image'              => $mainImage,
            'address'                 => $this->address,
            'description'             => $this->description,
            'ticket_price'            => (float) $this->ticket_price,
            'ticket_price_formatted'  => GeneralHelper::formatRupiah((float) $this->ticket_price),
            'estimated_cost'         => $this->estimated_cost ? (float) $this->estimated_cost : null,
            'estimated_cost_formatted' => $this->estimated_cost ? GeneralHelper::formatRupiah((float) $this->estimated_cost) : null,
            'operating_hours'        => $operatingHours,
            'open_hour'              => $openHour,
            'close_hour'             => $closeHour,
            'average_rating'         => round($this->average_rating, 1),
            'category'                => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'icon' => $this->category->icon,
            ]),
            'facilities'              => $facilities,
            'status'                  => $this->status->value,
        ];
    }
}