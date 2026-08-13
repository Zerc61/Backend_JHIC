<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\Product;
use App\Models\TravelPackage;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'users' => [
                'total' => User::count(),
                'tourist' => User::where('role', 'tourist')->count(),
                'umkm' => User::where('role', 'umkm')->count(),
                'manager' => User::where('role', 'manager')->count(),
                'admin' => User::where('role', 'admin')->count(),
            ],
            'destinations' => [
                'total' => Destination::count(),
                'published' => Destination::where('status', 'published')->count(),
                'draft' => Destination::where('status', 'draft')->count(),
            ],
            'hotels' => [
                'total' => Hotel::count(),
                'published' => Hotel::where('status', 'published')->count(),
                'draft' => Hotel::where('status', 'draft')->count(),
            ],
            'travel_packages' => [
                'total' => TravelPackage::count(),
                'published' => TravelPackage::where('status', 'published')->count(),
            ],
            'bookings' => [
                'total' => Booking::count(),
                'pending' => Booking::where('status', 'pending')->count(),
                'paid' => Booking::where('status', 'paid')->count(),
                'completed' => Booking::where('status', 'completed')->count(),
                'cancelled' => Booking::where('status', 'cancelled')->count(),
                'total_revenue' => Booking::whereIn('status', ['paid', 'confirmed', 'completed'])->sum('total_price'),
            ],
            'umkm' => [
                'total' => Umkm::count(),
                'active' => Umkm::where('status', 'active')->count(),
                'pending' => Umkm::where('status', 'pending')->count(),
                'rejected' => Umkm::where('status', 'rejected')->count(),
            ],
            'products' => [
                'total' => Product::count(),
                'available' => Product::where('status', 'available')->count(),
                'pending' => Product::where('status', 'pending')->count(),
                'rejected' => Product::where('status', 'rejected')->count(),
            ],
            'orders' => [
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'completed' => Order::where('status', 'picked_up')->count(),
                'total_revenue' => Order::where('status', 'picked_up')->sum('total_price'),
            ],
        ]);
    }
}