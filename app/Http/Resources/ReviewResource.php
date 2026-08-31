<?php

namespace App\Http\Resources;

use App\Models\ReviewVote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user();

        return [
            'id' => $this->id,
            'reviewable_type' => class_basename($this->reviewable_type),
            'reviewable_id' => $this->reviewable_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'photos' => collect($this->photos ?? [])
                ->map(fn ($path) => url("storage/{$path}"))
                ->values(),
            'video_url' => $this->video_url,
            'helpful_count' => (int) $this->helpful_count,
            'voted_by_me' => $this->when((bool) $me, fn () => ReviewVote::query()
                ->where('user_id', $me->id)
                ->where('review_id', $this->id)
                ->exists()),
            'response' => $this->response_text ? [
                'text' => $this->response_text,
                'created_at' => $this->response_at?->toIso8601String(),
                'responder' => $this->responder?->name,
            ] : null,
            'created_at' => $this->created_at->toIso8601String(),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar ? url("storage/{$this->user->avatar}") : null,
            ],
        ];
    }
}