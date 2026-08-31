<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'photos',
        'video_url',
        'helpful_count',
        'response_text',
        'response_at',
        'response_by',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'helpful_count' => 'integer',
            'photos' => 'array',
            'response_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'response_by');
    }

    public function votes()
    {
        return $this->hasMany(ReviewVote::class);
    }

    public function reviewable()
    {
        return $this->morphTo();
    }
}