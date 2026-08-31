<?php

namespace App\Policies;

use App\Models\Destination;
use App\Models\Product;
use App\Models\Review;
use App\Models\Umkm;
use App\Models\User;

class ReviewPolicy
{
    public function manage(User $user, Review $review): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return match ($review->reviewable_type) {
            Destination::class => (int) $review->reviewable?->manager_id === (int) $user->id,
            Umkm::class => (int) $review->reviewable?->user_id === (int) $user->id,
            Product::class => (int) $review->reviewable?->umkm?->user_id === (int) $user->id,
            default => false,
        };
    }

    public function vote(User $user, Review $review): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $review->user_id !== (int) $user->id;
    }
}