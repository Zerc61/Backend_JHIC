<?php

namespace App\Http\Controllers\Api;

use App\Enums\DestinationStatus;
use App\Enums\EventStatus;
use App\Enums\HotelStatus;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Product;
use App\Models\TravelPackage;
use App\Models\TransportTicket;
use App\Services\CatalogPriceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    private const TYPES = [
        'hotel' => 'Hotel',
        'destination' => 'Wisata',
        'transport' => 'Transport',
        'umkm' => 'Produk UMKM',
        'package' => 'Paket Wisata',
        'event' => 'Event',
    ];

    private const URL_MAP = [
        'hotel' => 'hotels',
        'destination' => 'destination',
        'transport' => 'transport-tickets',
        'umkm' => 'umkm',
        'package' => 'packages',
        'event' => 'events',
    ];

    private const SORTS = ['relevance', 'price_asc', 'price_desc', 'rating', 'nearest', 'newest'];

    private ?string $q;

    private ?string $category;

    private ?float $minPrice;

    private ?float $maxPrice;

    private ?float $minRating;

    private ?float $lat;

    private ?float $lng;

    private ?float $radiusKm;

    private string $sort = 'relevance';

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q'         => 'nullable|string|max:120',
            'types'     => 'nullable|string|max:200',
            'category'  => 'nullable|string|max:120',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'rating'    => 'nullable|numeric|min:1|max:5',
            'sort'      => 'nullable|in:' . implode(',', self::SORTS),
            'lat'       => 'nullable|numeric|between:-90,90',
            'lng'       => 'nullable|numeric|between:-180,180',
            'radius'    => 'nullable|numeric|min:1|max:500',
            'page'      => 'nullable|integer|min:1',
            'per_page'  => 'nullable|integer|min:1|max:50',
        ]);

        $this->q = Str::lower(trim($request->q ?? ''));
        $this->category = Str::lower(trim($request->category ?? ''));
        $this->minPrice = $request->filled('min_price') ? (float) $request->min_price : null;
        $this->maxPrice = $request->filled('max_price') ? (float) $request->max_price : null;
        $this->minRating = $request->filled('rating') ? (float) $request->rating : null;
        $this->lat = $request->filled('lat') ? (float) $request->lat : null;
        $this->lng = $request->filled('lng') ? (float) $request->lng : null;
        $this->radiusKm = $request->filled('radius') ? (float) $request->radius : null;
        $this->sort = $request->filled('sort') ? (string) $request->sort : 'relevance';

        $requestedTypes = array_filter(array_map('trim', explode(',', (string) $request->types)));
        $types = $requestedTypes
            ? array_intersect($requestedTypes, array_keys(self::TYPES))
            : array_keys(self::TYPES);

        $records = [];
        foreach ($types as $type) {
            $records = array_merge($records, $this->collectRecords($type));
        }

        $records = $this->applyFilters($records);
        $this->sortRecords($records);

        $page = max(1, (int) ($request->page ?? 1));
        $perPage = min(50, max(1, (int) ($request->per_page ?? 12)));
        $total = count($records);
        $slice = array_slice($records, ($page - 1) * $perPage, $perPage);

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'data' => \App\Http\Resources\SearchResource::collection($paginator),
            'meta' => [
                'total' => $total,
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'types' => array_values($types),
            ],
        ]);
    }

    // ── Collection builders (SQL filtering) ───────────

    private function collectRecords(string $type): array
    {
        switch ($type) {
            case 'destination':
                return $this->collectDestinations();
            case 'hotel':
                return $this->collectHotels();
            case 'transport':
                return $this->collectTransportTickets();
            case 'umkm':
                return $this->collectProducts();
            case 'package':
                return $this->collectPackages();
            case 'event':
                return $this->collectEvents();
            default:
                return [];
        }
    }

    private function collectDestinations(): array
    {
        $query = Destination::query()
            ->where('status', DestinationStatus::PUBLISHED)
            ->when($this->q, fn ($q) => $q->where(function ($w) {
                $w->where('name', 'like', "%{$this->q}%")
                    ->orWhere('description', 'like', "%{$this->q}%")
                    ->orWhere('address', 'like', "%{$this->q}%");
            }))
            ->when($this->category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('name', 'like', "%{$this->category}%")))
            ->when($this->hasRadius(), fn ($q) => $q->whereRaw(self::distanceExpr() . ' <= ?', $this->radiusParams()))
            ->with('galleries:id,destination_id,image')
            ->withAvg('reviews as rating', 'rating')
            ->withCount('reviews')
            ->with('category:id,name');

        return $query->get()->map(function ($d) {
            return $this->base([
                'type'        => 'destination',
                'type_label'  => self::TYPES['destination'],
                'id'          => $d->id,
                'name'        => $d->name,
                'slug'        => $d->slug,
                'description' => $d->description,
                'image'       => $d->galleries->first()?->image,
                'address'     => $d->address,
                'lat'         => $d->latitude ? (float) $d->latitude : null,
                'lng'         => $d->longitude ? (float) $d->longitude : null,
                'price'       => $d->ticket_price !== null ? (float) $d->ticket_price : null,
                'rating'      => $d->rating,
                'rating_count'=> (int) $d->reviews_count,
                'created_at'  => $d->created_at?->getTimestamp() ?? 0,
                'extra'       => [
                    'category' => $d->category?->name,
                    'open_hour' => $d->open_hour,
                    'close_hour' => $d->close_hour,
                ],
                'category'    => $d->category?->name,
            ], $d->latitude, $d->longitude);
        })->all();
    }

    private function collectHotels(): array
    {
        $query = Hotel::query()
            ->where('status', HotelStatus::PUBLISHED->value)
            ->when($this->q, fn ($q) => $q->where(function ($w) {
                $w->where('name', 'like', "%{$this->q}%")
                    ->orWhere('description', 'like', "%{$this->q}%")
                    ->orWhere('address', 'like', "%{$this->q}%");
            }))
            ->when($this->category, fn ($q) => $q->whereHas('destination', fn ($d) => $d->where('name', 'like', "%{$this->category}%")))
            ->when($this->hasRadius(), fn ($q) => $q->whereRaw(self::distanceExpr() . ' <= ?', $this->radiusParams()))
            ->withMin(['rooms as min_price' => fn ($r) => $r->where('status', 'available')], 'price_per_night')
            ->withAvg('reviews as rating', 'rating')
            ->withCount('reviews')
            ->with('destination:id,name');

        return $query->get()->map(function ($h) {
            return $this->base([
                'type'        => 'hotel',
                'type_label'  => self::TYPES['hotel'],
                'id'          => $h->id,
                'name'        => $h->name,
                'slug'        => $h->slug,
                'description' => $h->description,
                'image'       => $h->thumbnail,
                'address'     => $h->address,
                'lat'         => $h->latitude ? (float) $h->latitude : null,
                'lng'         => $h->longitude ? (float) $h->longitude : null,
                'price'       => $h->min_price !== null ? (float) $h->min_price : null,
                'rating'      => $h->rating,
                'rating_count'=> (int) $h->reviews_count,
                'created_at'  => $h->created_at?->getTimestamp() ?? 0,
                'extra'       => [
                    'category' => $h->destination?->name,
                    'star_rating' => $h->star_rating,
                ],
                'category'    => $h->destination?->name,
            ], $h->latitude, $h->longitude);
        })->all();
    }

    private function collectTransportTickets(): array
    {
        $query = TransportTicket::query()
            ->where('status', 'available')
            ->where('available_seats', '>', 0)
            ->when($this->q, fn ($q) => $q->where(function ($w) {
                $w->where('provider', 'like', "%{$this->q}%")
                    ->orWhere('origin_name', 'like', "%{$this->q}%")
                    ->orWhere('destination_name', 'like', "%{$this->q}%")
                    ->orWhere('flight_number', 'like', "%{$this->q}%");
            }));

        return $query->get()->map(function ($t) {
            $name = "{$t->origin_name} → {$t->destination_name}";

            return $this->base([
                'type'        => 'transport',
                'type_label'  => self::TYPES['transport'],
                'id'          => $t->id,
                'name'        => $name,
                'slug'        => (string) $t->id,
                'description' => $t->provider . ($t->flight_number ? " · {$t->flight_number}" : ''),
                'image'       => null,
                'address'     => '',
                'lat'         => null,
                'lng'         => null,
                'price'       => $t->price_per_ticket !== null ? (float) $t->price_per_ticket : null,
                'rating'      => null,
                'rating_count'=> 0,
                'created_at'  => $t->created_at?->getTimestamp() ?? 0,
                'extra'       => [
                    'category' => $t->transport_mode,
                    'origin' => $t->origin_name,
                    'destination' => $t->destination_name,
                    'departure' => $t->departure_time?->toIso8601String(),
                    'class' => $t->class_type,
                ],
                'category'    => $t->transport_mode,
            ], null, null);
        })->all();
    }

    private function collectProducts(): array
    {
        $query = Product::query()
            ->where('status', 'available')
            ->when($this->q, fn ($q) => $q->where(function ($w) {
                $w->where('name', 'like', "%{$this->q}%")
                    ->orWhere('description', 'like', "%{$this->q}%")
                    ->orWhereHas('umkm', fn ($u) => $u->where('name', 'like', "%{$this->q}%"));
            }))
            ->when($this->category, fn ($q) => $q->whereHas('umkm', fn ($u) => $u->where('name', 'like', "%{$this->category}%")))
            ->when($this->hasRadius(), fn ($q) => $q->whereHas('umkm', fn ($u) => $u->whereRaw(self::distanceExpr('umkms.latitude', 'umkms.longitude') . ' <= ?', $this->radiusParams())))
            ->withAvg('reviews as rating', 'rating')
            ->withCount('reviews')
            ->with('umkm:id,name,slug,latitude,longitude');

        return $query->get()->map(function ($p) {
            $umkm = $p->umkm;

            return $this->base([
                'type'        => 'umkm',
                'type_label'  => self::TYPES['umkm'],
                'id'          => $p->id,
                'name'        => $p->name,
                'slug'        => $p->slug,
                'description' => $p->description,
                'image'       => $p->image,
                'address'     => $umkm?->address ?? '',
                'lat'         => $umkm && $umkm->latitude ? (float) $umkm->latitude : null,
                'lng'         => $umkm && $umkm->longitude ? (float) $umkm->longitude : null,
                'price'       => $p->price !== null ? (float) $p->price : null,
                'rating'      => $p->rating,
                'rating_count'=> (int) $p->reviews_count,
                'created_at'  => $p->created_at?->getTimestamp() ?? 0,
                'extra'       => [
                    'category' => $umkm?->name,
                    'unit' => $p->unit,
                    'merchant_slug' => $umkm?->slug,
                    'merchant' => $umkm?->name,
                ],
                'category'    => $umkm?->name,
            ], $umkm?->latitude, $umkm?->longitude);
        })->all();
    }

    private function collectPackages(): array
    {
        $query = TravelPackage::query()
            ->where('status', 'published')
            ->when($this->q, fn ($q) => $q->where(function ($w) {
                $w->where('name', 'like', "%{$this->q}%")
                    ->orWhere('description', 'like', "%{$this->q}%")
                    ->orWhereHas('destination', fn ($d) => $d->where('name', 'like', "%{$this->q}%"));
            }))
            ->when($this->category, fn ($q) => $q->whereHas('destination', fn ($d) => $d->where('name', 'like', "%{$this->category}%")))
            ->when($this->hasRadius(), fn ($q) => $q->whereHas('destination', fn ($d) => $d->whereRaw(self::distanceExpr('destinations.latitude', 'destinations.longitude') . ' <= ?', $this->radiusParams())))
            ->with('destination:id,name');

        $packages = $query->get();
        $destIds = $packages->pluck('destination_id')->filter();
        $destRatings = $destIds->isNotEmpty()
            ? Destination::query()->whereKey($destIds)
                ->withAvg('reviews as rating', 'rating')
                ->withCount('reviews')
                ->get()->keyBy('id')
            : collect();

        return $packages->map(function ($p) use ($destRatings) {
            $d = $destRatings->get($p->destination_id);

            return $this->base([
                'type'        => 'package',
                'type_label'  => self::TYPES['package'],
                'id'          => $p->id,
                'name'        => $p->name,
                'slug'        => $p->slug,
                'description' => $p->description,
                'image'       => $p->thumbnail,
                'address'     => $p->destination?->name ?? '',
                'lat'         => null,
                'lng'         => null,
                'price'       => $p->price_per_person !== null ? (float) $p->price_per_person : null,
                'rating'      => $d?->rating,
                'rating_count'=> $d ? (int) $d->reviews_count : 0,
                'created_at'  => $p->created_at?->getTimestamp() ?? 0,
                'extra'       => [
                    'category' => $p->destination?->name,
                    'duration_days' => $p->duration_days,
                    'duration_nights' => $p->duration_nights,
                ],
                'category'    => $p->destination?->name,
            ], null, null);
        })->all();
    }

    private function collectEvents(): array
    {
        $query = Event::query()
            ->whereNotIn('status', [EventStatus::FINISHED->value, EventStatus::CANCELLED->value])
            ->when($this->q, fn ($q) => $q->where(function ($w) {
                $w->where('title', 'like', "%{$this->q}%")
                    ->orWhere('description', 'like', "%{$this->q}%")
                    ->orWhere('location', 'like', "%{$this->q}%");
            }))
            ->when($this->category, fn ($q) => $q->whereHas('destination', fn ($d) => $d->where('name', 'like', "%{$this->category}%")))
            ->with('destination:id,name');

        return $query->get()->map(function ($e) {
            return $this->base([
                'type'        => 'event',
                'type_label'  => self::TYPES['event'],
                'id'          => $e->id,
                'name'        => $e->title,
                'slug'        => $e->slug,
                'description' => $e->description,
                'image'       => $e->image,
                'address'     => $e->location ?? '',
                'lat'         => null,
                'lng'         => null,
                'price'       => null,
                'rating'      => null,
                'rating_count'=> 0,
                'created_at'  => $e->created_at?->getTimestamp() ?? 0,
                'extra'       => [
                    'category' => $e->destination?->name,
                    'start_date' => $e->start_date?->toIso8601String(),
                    'end_date' => $e->end_date?->toIso8601String(),
                ],
                'category'    => $e->destination?->name,
            ], null, null);
        })->all();
    }

    // ── Shared helpers ────────────────────────────────

    private function base(array $r, $lat, $lng): array
    {
        $r['relevance'] = $this->relevance($r['name']);
        $r['distance'] = ($lat !== null && $lng !== null && $this->lat !== null && $this->lng !== null)
            ? $this->haversine($lat, $lng, $this->lat, $this->lng)
            : null;

        switch ($r['type']) {
            case 'transport':
                $r['url'] = '/transport-tickets';
                break;
            case 'umkm':
                $r['url'] = '/umkms/' . ($r['extra']['merchant_slug'] ?? '');
                break;
            default:
                $r['url'] = '/' . self::URL_MAP[$r['type']] . '/' . $r['slug'];
                break;
        }

        return $r;
    }

    private function relevance(string $name): int
    {
        if (! $this->q) {
            return 1;
        }

        $lower = Str::lower($name);
        if ($lower === $this->q) {
            return 100;
        }
        if (Str::startsWith($lower, $this->q)) {
            return 60;
        }
        if (Str::contains($lower, $this->q)) {
            return 20;
        }

        return 0;
    }

    private function applyFilters(array $records): array
    {
        $records = array_values(array_filter($records, function ($r) {
            if ($this->minRating !== null && ($r['rating'] === null || $r['rating'] < $this->minRating)) {
                return false;
            }
            if ($this->minPrice !== null && ($r['price'] === null || $r['price'] < $this->minPrice)) {
                return false;
            }
            if ($this->maxPrice !== null && ($r['price'] === null || $r['price'] > $this->maxPrice)) {
                return false;
            }
            if ($this->lat !== null && $this->lng !== null && $this->radiusKm !== null && ($r['distance'] === null || $r['distance'] > $this->radiusKm)) {
                return false;
            }

            return true;
        }));

        if ($this->q) {
            $records = array_values(array_filter($records, fn ($r) => $r['relevance'] > 0));
        }

        return $records;
    }

    private function sortRecords(array &$records): void
    {
        $sort = $this->sort;

        usort($records, function ($a, $b) use ($sort) {
            switch ($sort) {
                case 'price_asc':
                    return self::cmpNullable($a['price'], $b['price'], 'asc');
                case 'price_desc':
                    return self::cmpNullable($a['price'], $b['price'], 'desc');
                case 'rating':
                    return self::cmpNullable($a['rating'], $b['rating'], 'desc');
                case 'nearest':
                    return self::cmpNullable($a['distance'], $b['distance'], 'asc');
                case 'newest':
                    return $b['created_at'] <=> $a['created_at'];
                default:
                    if (self::cmp($a['relevance'], $b['relevance']) !== 0) {
                        return self::cmp($b['relevance'], $a['relevance']);
                    }
                    if (self::cmpNullable($a['rating'], $b['rating'], 'desc') !== 0) {
                        return self::cmpNullable($a['rating'], $b['rating'], 'desc');
                    }
                    return self::cmp(Str::lower($a['name']), Str::lower($b['name']));
            }
        });
    }

    private static function cmp($a, $b): int
    {
        return $a <=> $b;
    }

    private static function cmpNullable($a, $b, string $dir): int
    {
        $aIsNull = $a === null || $a === '';
        $bIsNull = $b === null || $b === '';

        if ($aIsNull && $bIsNull) {
            return 0;
        }
        if ($aIsNull) {
            return 1;
        }
        if ($bIsNull) {
            return -1;
        }

        return $dir === 'asc' ? $a <=> $b : $b <=> $a;
    }

    private function hasRadius(): bool
    {
        return $this->lat !== null && $this->lng !== null && $this->radiusKm !== null;
    }

    private function radiusParams(): array
    {
        return [$this->lat, $this->lng, $this->lat, $this->radiusKm];
    }

    private static function distanceExpr(string $latCol = 'latitude', string $lngCol = 'longitude'): string
    {
        return "(6371 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS({$latCol})) * COS(RADIANS({$lngCol}) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS({$latCol})))))";
    }

    private static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}