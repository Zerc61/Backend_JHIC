<?php

namespace App\Http\Requests\Admin\Destination;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // sudah di-filter middleware AdminOnly
    }

    public function rules(): array
    {
        $slugUnique = 'required|string|max:255|unique:destination_categories,slug';

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $slugUnique .= ',' . $this->category->id;
        }

        return [
            'name' => 'required|string|max:255',
            'slug' => $slugUnique,
            'icon' => 'nullable|string|max:255',
        ];
    }
}