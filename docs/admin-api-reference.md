# Admin API Reference - JHIC Backend (Laravel 13)

> Ringkasan semua controller admin, router, middleware, dan entitas terkait pada backend Laravel 13 JHIC.

## 1. Struktur Direktori Backend (Admin)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── Admin/                  # ← Controller admin semua di sini
│   │   │   │   │   ├── AdminBookingController.php
│   │   │   │   │   ├── AdminCategoryController.php
│   │   │   │   │   ├── AdminDashboardController.php
│   │   │   │   │   ├── AdminDestinationController.php
│   │   │   │   │   ├── AdminEventController.php
│   │   │   │   │   ├── AdminFacilityController.php
│   │   │   │   │   ├── AdminHotelController.php
│   │   │   │   │   ├── AdminHotelRoomController.php
│   │   │   │   │   ├── AdminOrderController.php
│   │   │   │   │   ├── AdminProductController.php
│   │   │   │   │   ├── AdminReviewController.php
│   │   │   │   │   ├── AdminTravelPackageController.php
│   │   │   │   │   ├── AdminTransportTicketController.php
│   │   │   │   │   ├── AdminUmkmController.php
│   │   │   │   │   └── AdminUserController.php
│   │   │   │   ├── Dashboard/              # Dashboard per-role
│   │   │   │   ├── Manager/
│   │   │   │   └── Umkm/
│   │   │   └── Middleware/                 # Middleware role
│   │   │       ├── AdminMiddleware.php
│   │   │       ├── AdminAccess.php
│   │   │       ├── AdminOnly.php
│   │   │       ├── ManagerMiddleware.php
│   │   │       └── UmkmMiddleware.php
│   │   └── Kernel.php
│   ├── Models/                            # Eloquent models
│   ├── Enums/                             # Backing enums (UserRole, status, dll.)
│   └── Providers/
├── routes/
│   └── api.php                            # ← Router admin ada di sini
├── config/
├── database/
└── bootstrap/app.php                      # Alias middleware 'admin', 'manager', 'umkm'
```

---

## 2. Router Admin (`routes/api.php`)

Semua route admin dikelompokkan di bawah prefix `admin` dengan middleware `auth:sanctum` + `admin`.

```php
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Users
    Route::apiResource('users', AdminUserController::class);

    // Destination Categories
    Route::apiResource('destination-categories', AdminCategoryController::class);

    // Facilities
    Route::apiResource('facilities', AdminFacilityController::class);

    // Destinations
    Route::apiResource('destinations', AdminDestinationController::class);
    Route::put('destinations/{destination}/assign-manager', [AdminDestinationController::class, 'assignManager']);
    Route::post('destinations/{destination}/galleries', [AdminDestinationController::class, 'storeGallery']);
    Route::delete('destinations/{destination}/galleries/{gallery}', [AdminDestinationController::class, 'destroyGallery']);

    // Hotels
    Route::apiResource('hotels', AdminHotelController::class);
    Route::put('hotels/{hotel}/assign-manager', [AdminHotelController::class, 'assignManager']);
    Route::post('hotels/{hotel}/galleries', [AdminHotelController::class, 'storeGallery']);
    Route::delete('hotels/{hotel}/galleries/{gallery}', [AdminHotelController::class, 'destroyGallery']);
    Route::apiResource('hotels.rooms', AdminHotelRoomController::class);

    // Travel Packages
    Route::apiResource('travel-packages', AdminTravelPackageController::class)->parameters(['travel-packages' => 'package']);
    Route::put('travel-packages/{package}/assign-manager', [AdminTravelPackageController::class, 'assignManager']);
    Route::post('travel-packages/{package}/galleries', [AdminTravelPackageController::class, 'storeGallery']);
    Route::delete('travel-packages/{package}/galleries/{gallery}', [AdminTravelPackageController::class, 'destroyGallery']);
    Route::post('travel-packages/{package}/schedules', [AdminTravelPackageController::class, 'scheduleStore']);
    Route::put('travel-packages/{package}/schedules/{schedule}', [AdminTravelPackageController::class, 'scheduleUpdate']);
    Route::delete('travel-packages/{package}/schedules/{schedule}', [AdminTravelPackageController::class, 'scheduleDestroy']);

    // Events
    Route::apiResource('events', AdminEventController::class);
    Route::post('events/{event}/galleries', [AdminEventController::class, 'storeGallery']);
    Route::delete('events/{event}/galleries/{gallery}', [AdminEventController::class, 'destroyGallery']);

    // Transport Tickets
    Route::apiResource('transport-tickets', AdminTransportTicketController::class);

    // UMKM Approval
    Route::get('umkms/pending', [AdminUmkmController::class, 'pending']);
    Route::get('umkms/rejected', [AdminUmkmController::class, 'rejected']);
    Route::put('umkms/{umkm}/approve', [AdminUmkmController::class, 'approve']);
    Route::put('umkms/{umkm}/reject', [AdminUmkmController::class, 'reject']);
    Route::apiResource('umkms', AdminUmkmController::class)->except(['store']);

    // Product Approval
    Route::get('products/pending', [AdminProductController::class, 'pending']);
    Route::get('products/rejected', [AdminProductController::class, 'rejected']);
    Route::put('products/{product}/approve', [AdminProductController::class, 'approve']);
    Route::put('products/{product}/reject', [AdminProductController::class, 'reject']);
    Route::apiResource('products', AdminProductController::class)->except(['store']);

    // Bookings
    Route::get('bookings', [AdminBookingController::class, 'index']);
    Route::get('bookings/{booking}', [AdminBookingController::class, 'show']);

    // Orders (UMKM)
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::get('orders/{order}', [AdminOrderController::class, 'show']);

    // Reviews
    Route::get('reviews', [AdminReviewController::class, 'index']);
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy']);
});
```

### Middleware Admin

`app/Http/Middleware/AdminMiddleware.php`

```php
class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        return $next($request);
    }
}
```

Aliase didaftarkan di `bootstrap/app.php`:

```php
$middleware->alias([
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'manager' => \App\Http\Middleware\ManagerMiddleware::class,
    'umkm' => \App\Http\Middleware\UmkmMiddleware::class,
]);
```

---

## 3. Controller Admin (Kode Lengkap)

Berikut seluruh kode controller admin yang ada di `app/Http/Controllers/Api/Admin/`.

### 3.1 `AdminDashboardController.php`

Controller statistik ringkasan (tidak ada model request body — hanya proyeksi count).

```php
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
```

### 3.2 `AdminCategoryController.php`

CRUD kategori destinasi.

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DestinationCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = DestinationCategory::query()
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:destination_categories,slug',
            'icon' => 'nullable|string|max:255',
        ]);

        $category = DestinationCategory::create($validated);

        return response()->json(['message' => 'Kategori berhasil dibuat.', 'data' => $category], 201);
    }

    public function show(DestinationCategory $destinationCategory): JsonResponse
    {
        $destinationCategory->loadCount('destinations');

        return response()->json(['data' => $destinationCategory]);
    }

    public function update(Request $request, DestinationCategory $destinationCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => "sometimes|string|max:255|unique:destination_categories,slug,{$destinationCategory->id}",
            'icon' => 'nullable|string|max:255',
        ]);

        $destinationCategory->update($validated);

        return response()->json(['message' => 'Kategori berhasil diupdate.', 'data' => $destinationCategory]);
    }

    public function destroy(DestinationCategory $destinationCategory): JsonResponse
    {
        if ($destinationCategory->destinations()->exists()) {
            return response()->json(['message' => 'Kategori masih memiliki destination. Hapus atau pindahkan dulu.'], 409);
        }

        $destinationCategory->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}
```

### 3.3 `AdminFacilityController.php`

CRUD fasilitas (many-to-many ke destinasi).

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFacilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $facilities = Facility::query()
            ->when($request->filled('search'), fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return response()->json($facilities);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $facility = Facility::create($validated);

        return response()->json(['message' => 'Fasilitas berhasil dibuat.', 'data' => $facility], 201);
    }

    public function show(Facility $facility): JsonResponse
    {
        return response()->json(['data' => $facility]);
    }

    public function update(Request $request, Facility $facility): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $facility->update($validated);

        return response()->json(['message' => 'Fasilitas berhasil diupdate.', 'data' => $facility]);
    }

    public function destroy(Facility $facility): JsonResponse
    {
        $facility->delete();

        return response()->json(['message' => 'Fasilitas berhasil dihapus.']);
    }
}
```

### 3.4 `AdminUserController.php`

CRUD user (admin tidak dapat menghapus akun admin lain).

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => ['required', new Enum(UserRole::class)],
            'status' => ['nullable', new Enum(UserStatus::class)],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = $validated['status'] ?? UserStatus::ACTIVE->value;

        $user = User::create($validated);

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'data' => $user,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['wallet', 'umkm']);

        return response()->json(['data' => $user]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$user->id}",
            'password' => 'sometimes|string|min:8',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:500',
            'role' => ['sometimes', new Enum(UserRole::class)],
            'status' => ['sometimes', new Enum(UserStatus::class)],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User berhasil diupdate.',
            'data' => $user,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json(['message' => 'Tidak bisa menghapus akun admin.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }
}
```

### 3.5 `AdminDestinationController.php`

CRUD destinasi + gallery + penempatan manager.

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\DestinationGallery;
use App\Models\DestinationCategory;
use App\Models\User;
use App\Enums\DestinationStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;

class AdminDestinationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Destination::with(['category', 'manager']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('destination_category_id', $request->category_id);
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $destinations = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($destinations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_category_id' => 'required|exists:destination_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'open_hour' => 'nullable|date_format:H:i',
            'close_hour' => 'nullable|date_format:H:i',
            'ticket_price' => 'nullable|numeric|min:0',
            'estimated_cost' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'status' => ['nullable', new Enum(DestinationStatus::class)],
        ]);

        $validated['slug'] = Destination::generateUniqueSlug($validated['name']);
        $validated['status'] = $validated['status'] ?? DestinationStatus::DRAFT->value;

        $destination = Destination::create($validated);

        return response()->json([
            'message' => 'Destination berhasil dibuat.',
            'data' => $destination->load('category', 'manager'),
        ], 201);
    }

    public function show(Destination $destination): JsonResponse
    {
        $destination->load([
            'category', 'manager', 'facilities',
            'galleries' => fn($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $destination]);
    }

    public function update(Request $request, Destination $destination): JsonResponse
    {
        $validated = $request->validate([
            'destination_category_id' => 'sometimes|exists:destination_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'address' => 'sometimes|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'open_hour' => 'nullable|date_format:H:i',
            'close_hour' => 'nullable|date_format:H:i',
            'ticket_price' => 'nullable|numeric|min:0',
            'estimated_cost' => 'nullable|numeric|min:0',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'status' => ['sometimes', new Enum(DestinationStatus::class)],
        ]);

        if (isset($validated['name']) && $validated['name'] !== $destination->name) {
            $validated['slug'] = Destination::generateUniqueSlug($validated['name']);
        }

        $destination->update($validated);

        return response()->json([
            'message' => 'Destination berhasil diupdate.',
            'data' => $destination->load('category', 'manager'),
        ]);
    }

    public function destroy(Destination $destination): JsonResponse
    {
        $destination->delete();

        return response()->json(['message' => 'Destination berhasil dihapus.']);
    }

    public function assignManager(Request $request, Destination $destination): JsonResponse
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:users,id',
        ]);

        $manager = User::find($validated['manager_id']);

        if (!$manager->isManager()) {
            return response()->json(['message' => 'User ini bukan manager.'], 422);
        }

        $destination->update(['manager_id' => $validated['manager_id']]);

        return response()->json([
            'message' => 'Manager berhasil ditetapkan.',
            'data' => $destination->load('manager'),
        ]);
    }

    public function storeGallery(Request $request, Destination $destination): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('destination-galleries', 'public');
        $validated['image'] = $path;

        $gallery = $destination->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(Destination $destination, DestinationGallery $gallery): JsonResponse
    {
        if ($gallery->destination_id !== $destination->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di destination ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }
}
```

### 3.6 `AdminHotelController.php`

CRUD hotel + gallery + assign manager.

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelGallery;
use App\Models\User;
use App\Enums\HotelStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;

class AdminHotelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Hotel::with(['manager', 'destination', 'rooms']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }
        if ($request->filled('destination_id')) {
            $query->where('destination_id', $request->destination_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $hotels = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($hotels);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'thumbnail' => 'nullable|string|max:500',
            'destination_id' => 'nullable|exists:destinations,id',
            'manager_id' => 'nullable|exists:users,id',
            'status' => ['nullable', new Enum(HotelStatus::class)],
        ]);

        $validated['slug'] = Hotel::generateUniqueSlug($validated['name']);
        $validated['status'] = $validated['status'] ?? HotelStatus::DRAFT->value;

        $hotel = Hotel::create($validated);

        return response()->json([
            'message' => 'Hotel berhasil dibuat.',
            'data' => $hotel->load('manager', 'destination'),
        ], 201);
    }

    public function show(Hotel $hotel): JsonResponse
    {
        $hotel->load([
            'manager', 'destination', 'rooms',
            'galleries' => fn($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $hotel]);
    }

    public function update(Request $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'address' => 'sometimes|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'thumbnail' => 'nullable|string|max:500',
            'destination_id' => 'nullable|exists:destinations,id',
            'manager_id' => 'nullable|exists:users,id',
            'status' => ['sometimes', new Enum(HotelStatus::class)],
        ]);

        if (isset($validated['name']) && $validated['name'] !== $hotel->name) {
            $validated['slug'] = Hotel::generateUniqueSlug($validated['name']);
        }

        $hotel->update($validated);

        return response()->json([
            'message' => 'Hotel berhasil diupdate.',
            'data' => $hotel->load('manager', 'destination'),
        ]);
    }

    public function destroy(Hotel $hotel): JsonResponse
    {
        $hotel->delete();

        return response()->json(['message' => 'Hotel berhasil dihapus.']);
    }

    public function assignManager(Request $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:users,id',
        ]);

        $manager = User::find($validated['manager_id']);
        if (!$manager->isManager()) {
            return response()->json(['message' => 'User ini bukan manager.'], 422);
        }

        $hotel->update(['manager_id' => $validated['manager_id']]);

        return response()->json([
            'message' => 'Manager berhasil ditetapkan.',
            'data' => $hotel->load('manager'),
        ]);
    }

    public function storeGallery(Request $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('hotel-galleries', 'public');
        $validated['image'] = $path;

        $gallery = $hotel->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(Hotel $hotel, HotelGallery $gallery): JsonResponse
    {
        if ($gallery->hotel_id !== $hotel->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di hotel ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }
}
```

### 3.7 `AdminHotelRoomController.php`

Nested resource kamar hotel (`hotels.rooms`).

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminHotelRoomController extends Controller
{
    public function index(Hotel $hotel): JsonResponse
    {
        $rooms = $hotel->rooms()->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $rooms]);
    }

    public function store(Request $request, Hotel $hotel): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'total_rooms' => 'nullable|integer|min:1',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'status' => 'nullable|in:available,unavailable',
        ]);

        $validated['status'] = $validated['status'] ?? 'available';

        $room = $hotel->rooms()->create($validated);

        return response()->json(['message' => 'Kamar berhasil ditambahkan.', 'data' => $room], 201);
    }

    public function show(Hotel $hotel, HotelRoom $room): JsonResponse
    {
        return response()->json(['data' => $room]);
    }

    public function update(Request $request, Hotel $hotel, HotelRoom $room): JsonResponse
    {
        if ($room->hotel_id !== $hotel->id) {
            return response()->json(['message' => 'Kamar tidak ditemukan di hotel ini.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'price_per_night' => 'sometimes|numeric|min:0',
            'total_rooms' => 'nullable|integer|min:1',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'status' => 'sometimes|in:available,unavailable',
        ]);

        $room->update($validated);

        return response()->json(['message' => 'Kamar berhasil diupdate.', 'data' => $room]);
    }

    public function destroy(Hotel $hotel, HotelRoom $room): JsonResponse
    {
        if ($room->hotel_id !== $hotel->id) {
            return response()->json(['message' => 'Kamar tidak ditemukan di hotel ini.'], 404);
        }

        $room->delete();

        return response()->json(['message' => 'Kamar berhasil dihapus.']);
    }
}
```

### 3.8 `AdminTravelPackageController.php`

CRUD paket traval + gallery + schedule + assign manager.

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelPackage;
use App\Models\TravelPackageGallery;
use App\Models\TravelPackageSchedule;
use App\Models\User;
use App\Enums\TravelPackageStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;

class AdminTravelPackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TravelPackage::with(['manager', 'destination', 'hotel', 'schedules']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $packages = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($packages);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'nullable|string|max:500',
            'destination_id' => 'nullable|exists:destinations,id',
            'hotel_id' => 'nullable|exists:hotels,id',
            'manager_id' => 'nullable|exists:users,id',
            'duration_days' => 'nullable|integer|min:1',
            'duration_nights' => 'nullable|integer|min:0',
            'price_per_person' => 'required|numeric|min:0',
            'max_travelers' => 'nullable|integer|min:1',
            'included_items' => 'nullable|array',
            'included_items.*' => 'string',
            'excluded_items' => 'nullable|array',
            'excluded_items.*' => 'string',
            'meals_included' => 'nullable|array',
            'benefits' => 'nullable|array',
            'terms_conditions' => 'nullable|string',
            'status' => ['nullable', new Enum(TravelPackageStatus::class)],
        ]);

        $validated['slug'] = TravelPackage::generateUniqueSlug($validated['name']);
        $validated['status'] = $validated['status'] ?? TravelPackageStatus::DRAFT->value;

        $package = TravelPackage::create($validated);

        return response()->json([
            'message' => 'Travel package berhasil dibuat.',
            'data' => $package->load('manager', 'destination', 'hotel'),
        ], 201);
    }

    public function show(TravelPackage $package): JsonResponse
    {
        $package->load([
            'manager', 'destination', 'hotel',
            'schedules' => fn($q) => $q->orderBy('departure_date'),
            'galleries' => fn($q) => $q->orderBy('sort_order'),
        ]);

        return response()->json(['data' => $package]);
    }

    public function update(Request $request, TravelPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'thumbnail' => 'nullable|string|max:500',
            'destination_id' => 'nullable|exists:destinations,id',
            'hotel_id' => 'nullable|exists:hotels,id',
            'manager_id' => 'nullable|exists:users,id',
            'duration_days' => 'nullable|integer|min:1',
            'duration_nights' => 'nullable|integer|min:0',
            'price_per_person' => 'sometimes|numeric|min:0',
            'max_travelers' => 'nullable|integer|min:1',
            'included_items' => 'nullable|array',
            'included_items.*' => 'string',
            'excluded_items' => 'nullable|array',
            'excluded_items.*' => 'string',
            'meals_included' => 'nullable|array',
            'benefits' => 'nullable|array',
            'terms_conditions' => 'nullable|string',
            'status' => ['sometimes', new Enum(TravelPackageStatus::class)],
        ]);

        if (isset($validated['name']) && $validated['name'] !== $package->name) {
            $validated['slug'] = TravelPackage::generateUniqueSlug($validated['name']);
        }

        $package->update($validated);

        return response()->json([
            'message' => 'Travel package berhasil diupdate.',
            'data' => $package->load('manager', 'destination', 'hotel'),
        ]);
    }

    public function destroy(TravelPackage $package): JsonResponse
    {
        $package->delete();

        return response()->json(['message' => 'Travel package berhasil dihapus.']);
    }

    public function assignManager(Request $request, TravelPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'manager_id' => 'required|exists:users,id',
        ]);

        $manager = User::find($validated['manager_id']);
        if (!$manager->isManager()) {
            return response()->json(['message' => 'User ini bukan manager.'], 422);
        }

        $package->update(['manager_id' => $validated['manager_id']]);

        return response()->json([
            'message' => 'Manager berhasil ditetapkan.',
            'data' => $package->load('manager'),
        ]);
    }

    public function storeGallery(Request $request, TravelPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('travel-package-galleries', 'public');
        $validated['image'] = $path;

        $gallery = $package->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(TravelPackage $package, TravelPackageGallery $gallery): JsonResponse
    {
        if ($gallery->travel_package_id !== $package->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di package ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }

    public function scheduleStore(Request $request, TravelPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'departure_date' => 'required|date|after:today',
            'return_date' => 'required|date|after:departure_date',
            'max_capacity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'pickup_location' => 'nullable|string|max:500',
            'pickup_time' => 'nullable|date_format:H:i',
            'vehicle_info' => 'nullable|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:20',
        ]);

        $schedule = $package->schedules()->create($validated);

        return response()->json(['message' => 'Jadwal berhasil ditambahkan.', 'data' => $schedule], 201);
    }

    public function scheduleUpdate(Request $request, TravelPackage $package, TravelPackageSchedule $schedule): JsonResponse
    {
        if ($schedule->travel_package_id !== $package->id) {
            return response()->json(['message' => 'Jadwal tidak ditemukan di package ini.'], 404);
        }

        $validated = $request->validate([
            'departure_date' => 'sometimes|date',
            'return_date' => 'sometimes|date|after:departure_date',
            'max_capacity' => 'sometimes|integer|min:1',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:available,full,cancelled',
            'pickup_location' => 'nullable|string|max:500',
            'pickup_time' => 'nullable|date_format:H:i',
            'vehicle_info' => 'nullable|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'driver_phone' => 'nullable|string|max:20',
        ]);

        $schedule->update($validated);

        return response()->json(['message' => 'Jadwal berhasil diupdate.', 'data' => $schedule]);
    }

    public function scheduleDestroy(TravelPackage $package, TravelPackageSchedule $schedule): JsonResponse
    {
        if ($schedule->travel_package_id !== $package->id) {
            return response()->json(['message' => 'Jadwal tidak ditemukan di package ini.'], 404);
        }

        $schedule->delete();

        return response()->json(['message' => 'Jadwal berhasil dihapus.']);
    }
}
```

### 3.9 `AdminEventController.php`

CRUD event + gallery.

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGallery;
use App\Enums\EventStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Storage;

class AdminEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::with(['destination', 'creator']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('destination_id')) {
            $query->where('destination_id', $request->destination_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        $events = $query->orderBy('start_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($events);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'destination_id' => 'nullable|exists:destinations,id',
            'location' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'status' => ['nullable', new Enum(EventStatus::class)],
        ]);

        $validated['slug'] = Event::generateUniqueSlug($validated['title']);
        $validated['created_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? EventStatus::UPCOMING->value;

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Event berhasil dibuat.',
            'data' => $event->load('destination', 'creator'),
        ], 201);
    }

    public function show(Event $event): JsonResponse
    {
        $event->load(['destination', 'creator', 'galleries']);

        return response()->json(['data' => $event]);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'destination_id' => 'nullable|exists:destinations,id',
            'location' => 'nullable|string|max:500',
            'image' => 'nullable|string|max:500',
            'status' => ['sometimes', new Enum(EventStatus::class)],
        ]);

        if (isset($validated['title']) && $validated['title'] !== $event->title) {
            $validated['slug'] = Event::generateUniqueSlug($validated['title']);
        }

        $event->update($validated);

        return response()->json([
            'message' => 'Event berhasil diupdate.',
            'data' => $event->load('destination', 'creator'),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(['message' => 'Event berhasil dihapus.']);
    }

    public function storeGallery(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'caption' => 'nullable|string|max:255',
        ]);

        $path = $request->file('image')->store('event-galleries', 'public');
        $validated['image'] = $path;

        $gallery = $event->galleries()->create($validated);

        return response()->json(['message' => 'Galeri berhasil ditambahkan.', 'data' => $gallery], 201);
    }

    public function destroyGallery(Event $event, EventGallery $gallery): JsonResponse
    {
        if ($gallery->event_id !== $event->id) {
            return response()->json(['message' => 'Galeri tidak ditemukan di event ini.'], 404);
        }

        $gallery->delete();

        return response()->json(['message' => 'Galeri berhasil dihapus.']);
    }
}
```

### 3.10 `AdminTransportTicketController.php`

CRUD tiket transportasi (mode enum transport).

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportTicket;
use App\Enums\TransportMode;
use App\Enums\TransportTicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class AdminTransportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TransportTicket::query();

        if ($request->filled('transport_mode')) {
            $query->where('transport_mode', $request->transport_mode);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('origin_code')) {
            $query->where('origin_code', $request->origin_code);
        }
        if ($request->filled('destination_code')) {
            $query->where('destination_code', $request->destination_code);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('provider', 'like', "%{$search}%")
                  ->orWhere('origin_name', 'like', "%{$search}%")
                  ->orWhere('destination_name', 'like', "%{$search}%")
                  ->orWhere('flight_number', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('departure_time', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|max:255',
            'transport_mode' => ['required', new Enum(TransportMode::class)],
            'origin_code' => 'required|string|max:10',
            'origin_name' => 'required|string|max:255',
            'destination_code' => 'required|string|max:10',
            'destination_name' => 'required|string|max:255',
            'flight_number' => 'nullable|string|max:20',
            'departure_time' => 'required|date',
            'arrival_time' => 'required|date|after:departure_time',
            'duration_minutes' => 'required|integer|min:1',
            'is_transit' => 'nullable|boolean',
            'transit_info' => 'nullable|string|max:500',
            'class_type' => 'nullable|string|max:100',
            'available_seats' => 'required|integer|min:0',
            'price_per_ticket' => 'required|numeric|min:0',
            'valid_until' => 'nullable|date',
            'raw_response' => 'nullable|array',
        ]);

        $validated['status'] = TransportTicketStatus::AVAILABLE->value;

        $ticket = TransportTicket::create($validated);

        return response()->json(['message' => 'Tiket transportasi berhasil ditambahkan.', 'data' => $ticket], 201);
    }

    public function show(TransportTicket $transportTicket): JsonResponse
    {
        $transportTicket->loadCount('bookings');

        return response()->json(['data' => $transportTicket]);
    }

    public function update(Request $request, TransportTicket $transportTicket): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'sometimes|string|max:255',
            'transport_mode' => ['sometimes', new Enum(TransportMode::class)],
            'origin_code' => 'sometimes|string|max:10',
            'origin_name' => 'sometimes|string|max:255',
            'destination_code' => 'sometimes|string|max:10',
            'destination_name' => 'sometimes|string|max:255',
            'flight_number' => 'nullable|string|max:20',
            'departure_time' => 'sometimes|date',
            'arrival_time' => 'sometimes|date|after:departure_time',
            'duration_minutes' => 'sometimes|integer|min:1',
            'is_transit' => 'nullable|boolean',
            'transit_info' => 'nullable|string|max:500',
            'class_type' => 'nullable|string|max:100',
            'available_seats' => 'sometimes|integer|min:0',
            'price_per_ticket' => 'sometimes|numeric|min:0',
            'status' => ['sometimes', new Enum(TransportTicketStatus::class)],
            'valid_until' => 'nullable|date',
            'raw_response' => 'nullable|array',
        ]);

        $transportTicket->update($validated);

        return response()->json(['message' => 'Tiket transportasi berhasil diupdate.', 'data' => $transportTicket]);
    }

    public function destroy(TransportTicket $transportTicket): JsonResponse
    {
        $transportTicket->delete();

        return response()->json(['message' => 'Tiket transportasi berhasil dihapus.']);
    }
}
```

### 3.11 `AdminUmkmController.php`

Approval / manajemen UMKM (index, pending, rejected, approve, reject, update, destroy, show).

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUmkmController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Umkm::with(['user', 'destination', 'category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('umkm_category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $umkms = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($umkms);
    }

    public function pending(Request $request): JsonResponse
    {
        $umkms = Umkm::with(['user', 'destination', 'category'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 15);

        return response()->json($umkms);
    }

    public function rejected(Request $request): JsonResponse
    {
        $umkms = Umkm::with(['user', 'destination', 'category'])
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($umkms);
    }

    public function show(Umkm $umkm): JsonResponse
    {
        $umkm->load(['user', 'destination', 'category', 'products']);

        return response()->json(['data' => $umkm]);
    }

    public function approve(Request $request, Umkm $umkm): JsonResponse
    {
        if ($umkm->status !== 'pending') {
            return response()->json([
                'message' => "UMKM ini statusnya {$umkm->status}, bukan pending. Tidak bisa di-approve.",
            ], 422);
        }

        $umkm->update(['status' => 'active', 'admin_note' => null]);

        return response()->json([
            'message' => 'UMKM berhasil di-approve.',
            'data' => $umkm->load('user'),
        ]);
    }

    public function reject(Request $request, Umkm $umkm): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        if ($umkm->status !== 'pending') {
            return response()->json([
                'message' => "UMKM ini statusnya {$umkm->status}, bukan pending. Tidak bisa di-reject.",
            ], 422);
        }

        $umkm->update(['status' => 'rejected', 'admin_note' => $validated['admin_note']]);

        return response()->json([
            'message' => 'UMKM berhasil di-reject.',
            'data' => $umkm->load('user'),
        ]);
    }

    public function update(Request $request, Umkm $umkm): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'sometimes|string|max:500',
            'phone' => 'sometimes|string|max:30',
            'opening_hours' => 'nullable|string|max:100',
            'status' => 'sometimes|in:active,inactive,pending,rejected',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $umkm->update($validated);

        return response()->json(['message' => 'UMKM berhasil diupdate.', 'data' => $umkm]);
    }

    public function destroy(Umkm $umkm): JsonResponse
    {
        $umkm->delete();

        return response()->json(['message' => 'UMKM berhasil dihapus.']);
    }
}
```

### 3.12 `AdminProductController.php`

Approval / manajemen produk UMKM.

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['umkm.user', 'umkm.destination']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('umkm_id')) {
            $query->where('umkm_id', $request->umkm_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $products = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($products);
    }

    public function pending(Request $request): JsonResponse
    {
        $products = Product::with(['umkm.user', 'umkm.destination'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 15);

        return response()->json($products);
    }

    public function rejected(Request $request): JsonResponse
    {
        $products = Product::with(['umkm.user', 'umkm.destination'])
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['umkm.user', 'umkm.destination', 'images']);

        return response()->json(['data' => $product]);
    }

    public function approve(Request $request, Product $product): JsonResponse
    {
        if (!in_array($product->status, ['pending', 'rejected'])) {
            return response()->json([
                'message' => "Produk ini statusnya {$product->status}, tidak bisa di-approve.",
            ], 422);
        }

        $product->update(['status' => 'available', 'admin_note' => null]);

        return response()->json([
            'message' => 'Produk berhasil di-approve.',
            'data' => $product->load('umkm.user'),
        ]);
    }

    public function reject(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => 'required|string|max:1000',
        ]);

        if (!in_array($product->status, ['pending', 'approved', 'available'])) {
            return response()->json([
                'message' => "Produk ini statusnya {$product->status}, tidak bisa di-reject.",
            ], 422);
        }

        $product->update(['status' => 'rejected', 'admin_note' => $validated['admin_note']]);

        return response()->json([
            'message' => 'Produk berhasil di-reject.',
            'data' => $product->load('umkm.user'),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:500',
            'status' => 'sometimes|in:pending,approved,rejected,available,unavailable',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $product->update($validated);

        return response()->json(['message' => 'Produk berhasil diupdate.', 'data' => $product]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }
}
```

### 3.13 `AdminBookingController.php`

Read-only daftar / detail booking (semua tipe booking).

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['user']);

        if ($request->filled('booking_type')) {
            $query->where('booking_type', $request->booking_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('booking_number', 'like', "%{$search}%");
        }

        $bookings = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        $bookings->transform(function ($booking) {
            $booking->load($this->getDetailRelation($booking->booking_type));
            return $booking;
        });

        return response()->json($bookings);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load(['user', $this->getDetailRelation($booking->booking_type)]);

        return response()->json(['data' => $booking]);
    }

    private function getDetailRelation(string $type): string
    {
        return match ($type) {
            'hotel' => 'hotelBooking.room',
            'transport_ticket' => 'ticketBookings.transportTicket',
            'travel_package' => 'packageBooking.travelPackage',
            'destination_ticket' => 'destinationTicketBooking.destination',
            'transportation' => 'transportationBooking.transportation',
            default => '',
        };
    }
}
```

### 3.14 `AdminOrderController.php`

Read-only daftar / detail order UMKM.

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'umkm']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('umkm_id')) {
            $query->where('umkm_id', $request->umkm_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('order_number', 'like', "%{$search}%");
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['user', 'umkm', 'items']);

        return response()->json(['data' => $order]);
    }
}
```

### 3.15 `AdminReviewController.php`

Read + delete review.

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'reviewable']);

        if ($request->filled('reviewable_type')) {
            $query->where('reviewable_type', $request->reviewable_type);
        }
        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($reviews);
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json(['message' => 'Review berhasil dihapus.']);
    }
}
```

---

## 4. Entitas / Model yang Berkaitan (Admin)

Berikut model Eloquent yang dipakai controller admin (semua ada di `app/Models/`):

| Model | Relasi utama | Digunakan oleh controller |
|---|---|---|
| `User` | `wallet`, `umkm`, `bookings` | AdminUser, AdminDestination, AdminHotel, AdminTravelPackage |
| `Destination` | `category`, `manager`, `facilities`, `galleries`, `hotels`, `events` | AdminDestination, AdminHotel |
| `DestinationCategory` | `destinations` | AdminCategory |
| `DestinationGallery` | `destination` | AdminDestination |
| `Facility` | `destinations` (belongsToMany) | AdminFacility |
| `Hotel` | `manager`, `destination`, `rooms`, `galleries` | AdminHotel, AdminHotelRoom |
| `HotelGallery` | `hotel` | AdminHotel |
| `HotelRoom` | `hotel` | AdminHotelRoom |
| `TravelPackage` | `manager`, `destination`, `hotel`, `schedules`, `galleries` | AdminTravelPackage |
| `TravelPackageGallery` | `travel_package` | AdminTravelPackage |
| `TravelPackageSchedule` | `travel_package` | AdminTravelPackage (scheduleStore/Update/Destroy) |
| `Event` | `destination`, `creator`, `galleries` | AdminEvent |
| `EventGallery` | `event` | AdminEvent |
| `TransportTicket` | `bookings` | AdminTransportTicket |
| `Booking` | `user`, polymorphic `bookingable` | AdminBooking |
| `Order` | `user`, `umkm`, `items` | AdminOrder |
| `OrderItem` | `order` | AdminOrder |
| `Umkm` | `user`, `destination`, `category`, `products` | AdminUmkm |
| `Product` | `umkm`, `images` | AdminProduct |
| `ProductImage` | `product` | AdminProduct |
| `Review` | `user`, `reviewable` (polymorphic) | AdminReview |

### Enums (Laravel 13 backed enums)

```
app/Enums/
├── UserRole.php          // admin, manager, umkm, tourist
├── UserStatus.php        // active, inactive, banned
├── DestinationStatus.php // published, draft
├── HotelStatus.php       // published, draft
├── TravelPackageStatus.php // draft, published, archived
├── EventStatus.php       // upcoming, ongoing, completed, cancelled
├── ProductStatus.php     // pending, approved, rejected, available, unavailable
├── TransportMode.php     // flight, train, bus, etc.
├── TransportTicketStatus.php // available, sold_out, cancelled
```

---

## 5. Struktur Proyek Frontend (Vue 3 + Vite)

```
frontend/
├── index.html
├── package.json
├── vite.config.js
├── tailwind.config.js
├── node_modules/
├── public/
│   └── assets/
└── src/
    ├── main.js
    ├── App.vue
    ├── style.css
    ├── assets/
    ├── components/
    │   ├── HelloWorld.vue
    │   ├── dashboard/        # komponen kartu statistik, modal, table, badge
    │   │   ├── StatCard.vue
    │   │   ├── StatRow.vue
    │   │   ├── FileUpload.vue
    │   │   ├── Modal.vue
    │   │   ├── StatusBadge.vue
    │   │   ├── DataTable.vue
    │   │   └── SimpleList.vue
    │   └── layout/
    │       ├── AppLayout.vue
    │       └── AdminLayout.vue
    ├── composables/
    ├── pages/
    │   ├── auth/             # Login.vue, Register.vue, Profile.vue
    │   ├── admin/            # ← Halaman admin (role-based guard)
    │   │   ├── Dashboard.vue
    │   │   ├── users/        # Index, CreateEdit, Detail
    │   │   ├── destinations/ # Index, CreateEdit
    │   │   ├── hotels/       # Index, CreateEdit
    │   │   ├── packages/     # Index, CreateEdit
    │   │   ├── events/       # Index, CreateEdit
    │   │   ├── transport-tickets/
    │   │   ├── umkm-approval/
    │   │   ├── bookings/
    │   │   ├── orders/
    │   │   └── reviews/
    │   ├── dashboard/        # per-role dashboard (admin/manager/umkm)
    │   ├── destination/      # Detail, Explore, BookTicket
    │   ├── hotel/            # List, Detail
    │   ├── package/          # List, Detail
    │   ├── event/            # List, Detail
    │   ├── order/            # Checkout, Detail, MyOrders
    │   ├── wallet/
    │   ├── wishlist/
    │   ├── trip/             # Planner, MyTrips
    │   └── transportation/   # List, Detail
    ├── router/
    │   └── index.js          # ← Router dengan role-guarded admin routes
    ├── services/
    │   └── api.js            # Axios instance (baseURL, interceptors)
    ├── stores/
    │   ├── auth.js           # Pinia + cookie-based auth
    │   └── cart.js           # Pinia cart store
    └── utils/
```

### Router Admin (Vue Router)

```js
// src/router/index.js — admin route group
{
  path: "/admin",
  component: AdminLayout,
  meta: { role: "admin", requiresAuth: true },
  children: [
    { path: "", name: "admin.dashboard", component: AdminDashboard },
    { path: "users", name: "admin.users.index", component: AdminUsersIndex },
    { path: "users/create", name: "admin.users.create", component: AdminUsersCreateEdit },
    { path: "users/:id/edit", name: "admin.users.edit", component: AdminUsersCreateEdit },
    { path: "destinations", name: "admin.destinations.index", component: AdminDestinationsIndex },
    { path: "destinations/create", name: "admin.destinations.create", component: AdminDestinationsCreateEdit },
    { path: "destinations/:id/edit", name: "admin.destinations.edit", component: AdminDestinationsCreateEdit },
    { path: "hotels", name: "admin.hotels.index", component: () => import("@/pages/admin/hotels/Index.vue") },
    { path: "packages", name: "admin.packages.index", component: () => import("@/pages/admin/packages/Index.vue") },
    { path: "events", name: "admin.events.index", component: () => import("@/pages/admin/events/Index.vue") },
    { path: "transport-tickets", name: "admin.transport-tickets.index", component: () => import("@/pages/admin/transport-tickets/Index.vue") },
    { path: "umkm-approval", name: "admin.umkm-approval.index", component: () => import("@/pages/admin/umkm-approval/Index.vue") },
    { path: "bookings", name: "admin.bookings.index", component: () => import("@/pages/admin/bookings/Index.vue") },
    { path: "orders", name: "admin.orders.index", component: () => import("@/pages/admin/orders/Index.vue") },
    { path: "reviews", name: "admin.reviews.index", component: () => import("@/pages/admin/reviews/Index.vue") },
  ],
}
```

- Guard: `router.beforeEach` cek meta `role` vs `authStore.user?.role`.
- Auth store: cookie-based Sanctum, `fetchUser` di guard.

### Tech stack frontend

| Teknologi | Versi |
|-----------|-------|
| Vue | 3.5 (`^3.5.39`) |
| Vite | 8.1 (`^8.1.1`) |
| Pinia | 3.0 (`^3.0.4`) |
| Vue Router | 4.6 (`^4.6.4`) |
| Axios | 1.18 (`^1.18.1`) |
| TailwindCSS | 4.3 (`^4.3.2`) |
| Lucide | `lucide-vue-next` |
| Toast | `vue-toastification` |
| DayJS | date formatting |

### Axios service (`src/services/api.js`)

```js
import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api",
  withCredentials: true, // cookie Sanctum
});

export default api;
```

---

## 6. Struktur Proyek Backend (Admin)

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── Admin/        ← 14 controller + AdminDashboard
│   │   │   │   ├── Dashboard/
│   │   │   │   ├── Manager/
│   │   │   │   ├── Umkm/
│   │   │   │   └── AuthController.php
│   │   │   └── Middleware/
│   │   │       ├── AdminMiddleware.php
│   │   │       ├── AdminAccess.php
│   │   │       ├── AdminOnly.php
│   │   │       ├── ManagerMiddleware.php
│   │   │       └── UmkmMiddleware.php
│   ├── Models/
│   ├── Enums/
│   └── Providers/
├── routes/
│   ├── api.php   ← semua route (admin/manager/umkm/public)
│   └── console.php
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
└── bootstrap/app.php
```

### Ringkasan endpoint admin (method + path)

| Method | Path | Controller@method | Permission |
|--------|------|-------------------|------------|
| GET | `/api/admin/dashboard` | AdminDashboard@index | admin |
| GET\|POST\|PUT\|DELETE | `/api/admin/users` | AdminUser | admin |
| GET\|POST\|PUT\|DELETE | `/api/admin/destination-categories` | AdminCategory | admin |
| GET\|POST\|PUT\|DELETE | `/api/admin/facilities` | AdminFacility | admin |
| GET\|POST\|PUT\|DELETE | `/api/admin/destinations` | AdminDestination | admin |
| PUT | `/api/admin/destinations/{id}/assign-manager` | AdminDestination@assignManager | admin |
| POST | `/api/admin/destinations/{id}/galleries` | AdminDestination@storeGallery | admin |
| DELETE | `/api/admin/destinations/{id}/galleries/{gallery}` | AdminDestination@destroyGallery | admin |
| GET\|POST\|PUT\|DELETE | `/api/admin/hotels` | AdminHotel | admin |
| PUT | `/api/admin/hotels/{id}/assign-manager` | AdminHotel@assignManager | admin |
| POST\|DELETE | `/api/admin/hotels/{hotel}/galleries` | AdminHotel | admin |
| GET\|POST\|PUT\|DELETE | `/api/admin/hotels/{hotel}/rooms` | AdminHotelRoom (nested resource) | admin |
| GET\|POST\|PUT\|DELETE | `/api/admin/travel-packages` | AdminTravelPackage | admin |
| PUT | `/api/admin/travel-packages/{id}/assign-manager` | AdminTravelPackage@assignManager | admin |
| POST\|DELETE | `/api/admin/travel-packages/{package}/galleries` | AdminTravelPackage | admin |
| POST\|PUT\|DELETE | `/api/admin/travel-packages/{package}/schedules` | scheduleStore/Update/Destroy | admin |
| GET\|POST\|PUT\|DELETE | `/api/admin/events` | AdminEvent | admin |
| POST\|DELETE | `/api/admin/events/{event}/galleries` | AdminEvent | admin |
| GET\|POST\|PUT\|DELETE | `/api/admin/transport-tickets` | AdminTransportTicket | admin |
| GET (`/pending`, `/rejected`) + PUT (`/approve`, `/reject`) + apiResource | `/api/admin/umkms` | AdminUmkm (store excluded) | admin |
| GET (`/pending`, `/rejected`) + PUT (`/approve`, `/reject`) + apiResource | `/api/admin/products` | AdminProduct (store excluded) | admin |
| GET (index + show) | `/api/admin/bookings` | AdminBooking (read only) | admin |
| GET (index + show) | `/api/admin/orders` | AdminOrder (read only) | admin |
| GET + DELETE | `/api/admin/reviews` | AdminReview | admin |

---

## 7. Catatan Penting

### Middleware `admin`

Aliased via `bootstrap/app.php` → `\App\Http\Middleware\AdminMiddleware`.
Cek `$user->isAdmin()` (berdasarkan enum `UserRole::ADMIN`).

### Persisted-relations yang selalu diload

- Destination: `category`, `manager`, `facilities`, `galleries`
- Hotel: `manager`, `destination`, `rooms`, `galleries`
- TravelPackage: `manager`, `destination`, `hotel`, `schedules`, `galleries`
- Event: `destination`, `creator`, `galleries`
- UMKM: `user`, `destination`, `category`, `products`
- Product: `umkm.user`, `umkm.destination`, `images`
- Booking: relasi polymorphic detail (via `getDetailRelation()`)
- Order: `user`, `umkm`, `items`

### Upload gambar

Gambar disimpan ke disk `public`, path relatif (`directory-galleries/...`) disimpan di kolom `image`.
- Destination: `destination-galleries`
- Hotel: `hotel-galleries`
- TravelPackage: `travel-package-galleries`
- Event: `event-galleries`

### Status workflow

1. **UMKM**: `pending → active` (approve) / `pending → rejected` (reject)
2. **Product**: `pending → available` (approve) / `pending|approved → rejected` (reject)
3. **Destination/Hotel/TravelPackage**: `draft → published`
