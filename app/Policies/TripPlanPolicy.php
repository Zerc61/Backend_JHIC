<?php

namespace App\Policies;

use App\Models\TripPlan;
use App\Models\User;

class TripPlanPolicy
{
    public function view(User $user, TripPlan $tripPlan): bool
    {
        return $tripPlan->user_id === $user->id;
    }

    public function delete(User $user, TripPlan $tripPlan): bool
    {
        return $tripPlan->user_id === $user->id;
    }
}