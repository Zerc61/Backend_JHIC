<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestinationGallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination_id',
        'image',
        'caption',
        'sort_order',
    ];

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }
}