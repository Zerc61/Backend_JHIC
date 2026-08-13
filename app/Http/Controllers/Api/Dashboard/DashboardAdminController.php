<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\TravelPackage;
use App\Models\Booking;
use App\Models\Umkm;
use App\Models\Product;
use App\Models\Order;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = [
            'total' => User::count(),
            'tourist' => User::where('role', UserRole::TOURIST)->count(),
            'umkm' => User::where('role', UserRole::UMKM)->count(),
            'manager' => User::where('role', UserRole::MANAGER)->count(),
            'admin' => User::where('role', UserRole::ADMIN)->count(),
        ];

        $destinations = [
            'total' => Destination::count(),
            'published' => Destination::where('status', 'published')->count(),
            'draft' => Destination::where('status', 'draft')->count(),
        ];

        $hotels = [
            'total' => Hotel::count(),
            'published' => Hotel::where('status', 'published')->count(),
            'draft' => Hotel::where('status', 'draft')->count(),
        ];

        $travel_packages = [
            'total' => TravelPackage::count(),
            'published' => TravelPackage::where('status', 'published')->count(),
        ];

        $bookings = [
            'total' => Booking::count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'paid' => Booking::where('status', 'paid')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'total_revenue' => Booking::whereIn('status', ['paid', 'confirmed', 'completed'])->sum('total_price'),
        ];

        $umkm = [
            'total' => Umkm::count(),
            'active' => Umkm::where('status', 'active')->count(),
            'pending' => Umkm::where('status', 'pending')->count(),
            'rejected' => Umkm::where('status', 'rejected')->count(),
        ];

        $products = [
            'total' => Product::count(),
            'available' => Product::where('status', 'available')->count(),
            'pending' => Product::where('status', 'pending')->count(),
            'rejected' => Product::where('status', 'rejected')->count(),
        ];

        $orders = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'completed' => Order::where('status', 'picked_up')->count(),
            'total_revenue' => Order::whereIn('status', ['paid', 'preparing', 'ready', 'picked_up'])->sum('total_price'),
        ];

        return response()->json([
            'users' => $users,
            'destinations' => $destinations,
            'hotels' => $hotels,
            'travel_packages' => $travel_packages,
            'bookings' => $bookings,
            'umkm' => $umkm,
            'products' => $products,
            'orders' => $orders,
        ]);
    }
}