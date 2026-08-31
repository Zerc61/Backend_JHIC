<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItineraryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_id' => $this->day_id,
            'slot' => $this->slot,
            'slot_label' => $this->slotLabel($this->slot),
            'type' => $this->type,
            'name' => $this->name,
            'image' => $this->image,
            'reference_id' => $this->reference_id,
            'custom_name' => $this->custom_name,
            'custom_note' => $this->custom_note,
            'estimated_cost' => (float) $this->estimated_cost,
            'estimated_cost_formatted' => \App\Helpers\GeneralHelper::formatRupiah((float) $this->estimated_cost),
            'duration_minutes' => $this->duration_minutes,
            'sort_order' => $this->sort_order,
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }
}