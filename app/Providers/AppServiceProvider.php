<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Policies\OrderPolicy;
use App\Models\Order;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Destination;
use App\Models\TravelPackage;
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

        // Model Destination/Hotel/TravelPackage/Event memakai getRouteKeyName = slug,
        // sedangkan panel admin & beberapa route user memakai ID.
        // Binding berikut menerima slug ATAU id agar keduanya tetap bekerja.
        Route::bind('destination', fn ($value) => Destination::where('slug', $value)->orWhere('id', $value)->firstOrFail());
        Route::bind('hotel', fn ($value) => Hotel::where('slug', $value)->orWhere('id', $value)->firstOrFail());
        Route::bind('package', fn ($value) => TravelPackage::where('slug', $value)->orWhere('id', $value)->firstOrFail());
        Route::bind('event', fn ($value) => Event::where('slug', $value)->orWhere('id', $value)->firstOrFail());
        Route::bind('booking', fn ($value) => Booking::where('booking_number', $value)->orWhere('id', $value)->firstOrFail());
    }
}