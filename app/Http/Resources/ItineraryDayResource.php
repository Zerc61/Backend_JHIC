<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItineraryDayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'date_label' => $this->indonesianDate($this->date),
            'day_number' => $this->sort_order + 1,
            'sort_order' => $this->sort_order,
            'items' => ItineraryItemResource::collection($this->whenLoaded('items')),
            'items_total' => (float) $this->items()->sum('estimated_cost'),
        ];
    }

    private function indonesianDate(\Carbon\CarbonInterface $date): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return "{$days[$date->dayOfWeek]}, {$date->day} {$months[$date->month]} {$date->year}";
    }
}