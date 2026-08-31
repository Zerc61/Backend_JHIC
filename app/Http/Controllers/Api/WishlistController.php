<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistCollectionResource;
use App\Http\Resources\WishlistItemResource;
use App\Http\Resources\WishlistResource;
use App\Models\Destination;
use App\Models\Wishlist;
use App\Models\WishlistCollection;
use App\Models\WishlistItem;
use App\Services\CatalogPriceResolver;
use App\Services\PriceTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(
        private CatalogPriceResolver $catalog,
        private PriceTrackingService $priceTracking
    ) {
    }

    // ── Legacy (koleksi Default) ────────────────────

    public function index(Request $request): JsonResponse
    {
        $wishlists = Wishlist::with('wishlistable.category', 'wishlistable.galleries')
            ->where('user_id', $request->user()->id)
            ->where('wishlistable_type', Destination::class)
            ->latest()
            ->get();

        return response()->json(['data' => WishlistResource::collection($wishlists)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['destination_id' => 'required|exists:destinations,id']);

        $collection = $this->getOrCreateDefault($request->user()->id);
        $this->addItemToCollection($collection, 'destination', (int) $request->destination_id);

        $wishlist = Wishlist::firstOrCreate([
            'user_id'           => $request->user()->id,
            'wishlistable_type' => Destination::class,
            'wishlistable_id'   => $request->destination_id,
        ]);

        return response()->json([
            'message' => 'Ditambahkan ke wishlist',
            'data'    => new WishlistResource($wishlist),
        ]);
    }

    public function destroy(Request $request, int $destinationId): JsonResponse
    {
        $deleted = Wishlist::where('user_id', $request->user()->id)
            ->where('wishlistable_type', Destination::class)
            ->where('wishlistable_id', $destinationId)
            ->delete();

        $collection = WishlistCollection::where('user_id', $request->user()->id)
            ->where('is_default', true)
            ->first();

        if ($collection) {
            WishlistItem::where('collection_id', $collection->id)
                ->where('wishlistable_type', Destination::class)
                ->where('wishlistable_id', $destinationId)
                ->delete();
        }

        if (! $deleted && ! $collection) {
            return response()->json(['message' => 'Wishlist tidak ditemukan'], 404);
        }

        return response()->json(['message' => 'Dihapus dari wishlist']);
    }

    public function check(Request $request, int $destinationId): JsonResponse
    {
        $exists = WishlistItem::query()
            ->whereHas('collection', fn ($q) => $q->where('user_id', $request->user()->id))
            ->where('wishlistable_type', Destination::class)
            ->where('wishlistable_id', $destinationId)
            ->exists();

        return response()->json(['is_wishlisted' => $exists]);
    }

    // ── Collections ─────────────────────────────────

    public function getCollections(Request $request): JsonResponse
    {
        $collections = WishlistCollection::with(['items'])
            ->where('user_id', $request->user()->id)
            ->orderByRaw('is_default DESC')
            ->latest('id')
            ->get();

        return response()->json(['data' => WishlistCollectionResource::collection($collections)]);
    }

    public function storeCollection(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $collection = WishlistCollection::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'is_default' => false,
            'is_public' => false,
        ]);

        $collection->load('items');

        return response()->json([
            'message' => 'Koleksi dibuat',
            'data' => new WishlistCollectionResource($collection),
        ], 201);
    }

    public function showCollection(Request $request, WishlistCollection $collection): JsonResponse
    {
        $this->authorizeOwner($request, $collection);

        $collection->load(['items.priceHistories']);

        return response()->json(['data' => new WishlistCollectionResource($collection)]);
    }

    public function updateCollection(Request $request, WishlistCollection $collection): JsonResponse
    {
        $this->authorizeOwner($request, $collection);

        $request->validate([
            'name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'nullable|boolean',
        ]);

        $collection->fill($request->only(['name', 'description', 'is_public']))->save();

        return response()->json([
            'message' => 'Koleksi diperbarui',
            'data' => new WishlistCollectionResource($collection->fresh(['items'])),
        ]);
    }

    public function destroyCollection(Request $request, WishlistCollection $collection): JsonResponse
    {
        $this->authorizeOwner($request, $collection);

        if ($collection->is_default) {
            return response()->json(['message' => 'Koleksi Default tidak bisa dihapus'], 422);
        }

        $collection->delete();

        return response()->json(['message' => 'Koleksi dihapus']);
    }

    public function addItem(Request $request, WishlistCollection $collection): JsonResponse
    {
        $this->authorizeOwner($request, $collection);

        $request->validate([
            'type' => 'required|in:' . implode(',', CatalogPriceResolver::TYPES),
            'reference_id' => 'required|integer',
            'target_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $class = CatalogPriceResolver::classFromType($request->type);
        if (! $class) {
            return response()->json(['message' => 'Tipe tidak dikenal'], 422);
        }

        $existsEntity = $class::query()->whereKey($request->reference_id)->exists();
        if (! $existsEntity) {
            return response()->json(['message' => 'Referensi tidak ditemukan'], 422);
        }

        $item = $this->addItemToCollection(
            $collection,
            $request->type,
            (int) $request->reference_id,
            $request->target_price,
            $request->note
        );

        return response()->json([
            'message' => 'Item ditambahkan ke koleksi',
            'data' => new WishlistItemResource($item),
        ], 201);
    }

    public function updateItem(Request $request, WishlistItem $item): JsonResponse
    {
        $this->authorizeOwner($request, $item->collection);

        $request->validate([
            'target_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $item->fill($request->only(['target_price', 'note']))->save();

        return response()->json([
            'message' => 'Item diperbarui',
            'data' => new WishlistItemResource($item->fresh(['priceHistories'])),
        ]);
    }

    public function removeItem(Request $request, WishlistItem $item): JsonResponse
    {
        $this->authorizeOwner($request, $item->collection);

        $item->delete();

        return response()->json(['message' => 'Item dihapus']);
    }

    public function moveItem(Request $request, WishlistItem $item): JsonResponse
    {
        $this->authorizeOwner($request, $item->collection);

        $request->validate(['collection_id' => 'required|exists:wishlist_collections,id']);

        $target = WishlistCollection::where('id', $request->collection_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $item->forceFill(['collection_id' => $target->id])->save();

        return response()->json([
            'message' => 'Item dipindahkan',
            'data' => new WishlistItemResource($item->fresh(['priceHistories'])),
        ]);
    }

    public function shareCollection(Request $request, WishlistCollection $collection): JsonResponse
    {
        $this->authorizeOwner($request, $collection);

        $collection->forceFill(['is_public' => true])->save();
        $token = $collection->ensureShareToken();

        return response()->json([
            'message' => 'Koleksi siap dibagikan',
            'share_url' => url('/share/wishlist/' . $token),
            'token' => $token,
            'is_public' => true,
        ]);
    }

    public function sharedView(Request $request, string $token): JsonResponse
    {
        $collection = WishlistCollection::where('share_token', $token)
            ->where('is_public', true)
            ->with(['items.priceHistories'])
            ->first();

        if (! $collection) {
            abort(404, 'Koleksi tidak ditemukan atau tidak dibagikan.');
        }

        return response()->json(['data' => new WishlistCollectionResource($collection)]);
    }

    // ── Helpers ─────────────────────────────────────

    private function getOrCreateDefault(int $userId): WishlistCollection
    {
        return WishlistCollection::firstOrCreate(
            ['user_id' => $userId, 'is_default' => true],
            ['name' => 'Default']
        );
    }

    private function addItemToCollection(
        WishlistCollection $collection,
        string $type,
        int $referenceId,
        ?float $targetPrice = null,
        ?string $note = null
    ): WishlistItem {
        $class = CatalogPriceResolver::classFromType($type);

        $item = WishlistItem::firstOrCreate(
            [
                'collection_id' => $collection->id,
                'wishlistable_type' => $class,
                'wishlistable_id' => $referenceId,
            ],
            [
                'target_price' => $targetPrice,
                'note' => $note,
            ]
        );

        if ($targetPrice !== null) {
            $item->forceFill(['target_price' => $targetPrice])->save();
        }

        $this->priceTracking->trackItem($item);

        return $item;
    }

    private function authorizeOwner(Request $request, WishlistCollection $collection): void
    {
        if (! $collection->isOwner($request->user()->id)) {
            abort(403, 'Kamu tidak memiliki akses ke koleksi ini.');
        }
    }
}