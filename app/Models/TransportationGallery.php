<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportationGallery extends Model
{
    protected $fillable = ['transportation_id', 'image', 'caption', 'sort_order'];

    public function transportation(): BelongsTo
    {
        return $this->belongsTo(Transportation::class);
    }
}