<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Destination;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\Umkm;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function __construct(private LoyaltyService $loyaltyService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => 'required|in:Destination,Umkm,Product',
            'reviewable_id' => 'required|integer',
        ]);

        $reviews = Review::with(['user', 'responder'])
            ->where('reviewable_type', "App\\Models\\{$request->reviewable_type}")
            ->where('reviewable_id', $request->reviewable_id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $this->authorizeViewOwnReviews($request->user());

        $query = Review::with(['user', 'responder']);

        if ($request->user()->isManager()) {
            $destinationIds = Destination::where('manager_id', $request->user()->id)->pluck('id');
            $query->where('reviewable_type', Destination::class)
                ->whereIn('reviewable_id', $destinationIds);
        } elseif ($request->user()->isUmkm()) {
            $umkmIds = Umkm::where('user_id', $request->user()->id)->pluck('id');
            $productIds = Product::whereIn('umkm_id', $umkmIds)->pluck('id');
            $query->where(function ($q) use ($umkmIds, $productIds) {
                $q->where('reviewable_type', Umkm::class)->whereIn('reviewable_id', $umkmIds)
                    ->orWhere('reviewable_type', Product::class)->whereIn('reviewable_id', $productIds);
            });
        } else {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        $reviews = $query->latest()->paginate(10);

        return response()->json([
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'reviewable_type' => 'required|in:Destination,Umkm,Product',
            'reviewable_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'video_url' => 'nullable|url|max:500',
            'photos.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $review = DB::transaction(function () use ($request) {
            $review = Review::create([
                'user_id' => $request->user()->id,
                'reviewable_type' => "App\\Models\\{$request->reviewable_type}",
                'reviewable_id' => $request->reviewable_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'video_url' => $request->video_url,
            ]);

            $photos = collect($request->file('photos', []))
                ->map(fn ($file) => $file->store("reviews/{$review->id}", 'public'))
                ->values();

            if ($photos->isNotEmpty()) {
                $review->update(['photos' => $photos->all()]);
            }

            return $review;
        });

        Notification::createNewReview($review);

        $this->loyaltyService->rewardReview($request->user(), $review, count($review->photos) > 0);

        return response()->json([
            'message' => 'Review berhasil ditambahkan',
            'data' => new ReviewResource($review->load(['user', 'responder'])),
        ], 201);
    }

    public function vote(Request $request, Review $review): JsonResponse
    {
        if (!$this->authorize('vote', $review)) {
            throw ValidationException::withMessages(['review_id' => 'Anda tidak bisa mem-vote review sendiri']);
        }

        $existing = ReviewVote::where('user_id', $request->user()->id)
            ->where('review_id', $review->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $review->decrement('helpful_count');
            $review->refresh();

            return response()->json([
                'message' => 'Vote dihapus',
                'helpful_count' => max(0, (int) $review->helpful_count),
                'voted_by_me' => false,
            ]);
        }

        ReviewVote::create([
            'user_id' => $request->user()->id,
            'review_id' => $review->id,
        ]);
        $review->increment('helpful_count');
        $review->refresh();

        return response()->json([
            'message' => 'Review ditandai membantu',
            'helpful_count' => (int) $review->helpful_count,
            'voted_by_me' => true,
        ], 201);
    }

    public function respond(Request $request, Review $review): JsonResponse
    {
        $this->authorize('manage', $review);

        $request->validate([
            'response_text' => 'required|string|max:2000',
        ]);

        $review->update([
            'response_text' => $request->response_text,
            'response_at' => now(),
            'response_by' => $request->user()->id,
        ]);

        Notification::createReviewResponse($review);

        return response()->json([
            'message' => 'Balasan berhasil disimpan',
            'data' => new ReviewResource($review->fresh()->load(['user', 'responder'])),
        ]);
    }

    private function authorizeViewOwnReviews($user): void
    {
        if (!$user->isManager() && !$user->isUmkm()) {
            abort(403, 'Akses ditolak');
        }
    }
}