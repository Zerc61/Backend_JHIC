<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Policies\OrderPolicy;
use App\Models\Order;
use App\Policies\TripPlanPolicy;
use App\Models\TripPlan;
use App\Models\User;
use App\Observers\UserObserver;
use App\Services\TransportTicket\TransportTicketServiceInterface;
use App\Services\TransportTicket\MockTransportTicketService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TransportTicketServiceInterface::class,
            MockTransportTicketService::class
        );
    }

    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(TripPlan::class, TripPlanPolicy::class);
        User::observe(UserObserver::class);
    }
}