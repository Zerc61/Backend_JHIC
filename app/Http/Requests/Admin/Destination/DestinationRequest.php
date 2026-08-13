<?php

namespace App\Http\Requests\Admin\Destination;

use Illuminate\Foundation\Http\FormRequest;

class DestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $slugUnique = 'required|string|max:255|unique:destinations,slug';

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $slugUnique .= ',' . $this->destination->id;
        }

        return [
            'destination_category_id' => 'required|exists:destination_categories,id',
            'manager_id'              => 'nullable|exists:users,id',
            'name'                    => 'required|string|max:255',
            'slug'                    => $slugUnique,
            'description'             => 'required|string',
            'estimated_cost'          => 'nullable|numeric|min:0',
            'address'                 => 'required|string|max:500',
            'latitude'                => 'nullable|numeric|between:-90,90',
            'longitude'               => 'nullable|numeric|between:-180,180',
            'open_hour'               => 'nullable|date_format:H:i',
            'close_hour'              => 'nullable|date_format:H:i',
            'ticket_price'            => 'required|numeric|min:0',
            'phone'                   => 'nullable|string|max:255',
            'website'                 => 'nullable|string|max:255',
            'status'                  => 'required|in:published,draft,archived',

            // Galeri (array of objects)
            'galleries'               => 'nullable|array',
            'galleries.*.id'          => 'nullable|integer|exists:destination_galleries,id',
            'galleries.*.image'       => 'required|string|max:500',
            'galleries.*.caption'     => 'nullable|string|max:255',
            'galleries.*.sort_order'  => 'nullable|integer|min:0',

            // Fasilitas (array of IDs)
            'facilities'              => 'nullable|array',
            'facilities.*'            => 'integer|exists:facilities,id',
        ];
    }
}