<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $reviews = $product->reviews()->approved()->latest()->paginate(15);

        return response()->json([
            'reviews' => ReviewResource::collection($reviews->items()),
            'average' => round((float) $product->rating, 2),
            'total' => $reviews->total(),
        ]);
    }

    public function store(StoreReviewRequest $request, Product $product): JsonResponse
    {
        $user = $request->user();

        // One review per customer per product keeps ratings meaningful.
        $existing = $user
            ? $product->reviews()->where('user_id', $user->id)->first()
            : null;

        if ($existing) {
            return response()->json([
                'message' => 'You have already reviewed this product.',
            ], 422);
        }

        $review = $product->reviews()->create([
            'user_id' => $user?->id,
            'reviewer_name' => $user?->name ?? $request->input('reviewer_name'),
            'rating' => $request->integer('rating'),
            'comment' => $request->input('comment'),
            'is_approved' => true,
        ]);

        $this->refreshProductRating($product);

        return response()->json(['review' => new ReviewResource($review)], 201);
    }

    protected function refreshProductRating(Product $product): void
    {
        $stats = Review::query()
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        $product->update([
            'rating' => round((float) $stats->avg_rating, 2),
            'reviews_count' => (int) $stats->total,
        ]);
    }
}
