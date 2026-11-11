<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with('category')
            ->whereIn('id', $request->user()->wishlistItems()->pluck('product_id'))
            ->get();

        return response()->json(['products' => ProductResource::collection($products)]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $existing = $request->user()->wishlistItems()
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['inWishlist' => false]);
        }

        $request->user()->wishlistItems()->create($data);

        return response()->json(['inWishlist' => true], 201);
    }

    /**
     * Merge the guest localStorage wishlist into the account after login.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'max:200'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        foreach (array_unique($data['product_ids']) as $productId) {
            $request->user()->wishlistItems()->firstOrCreate(['product_id' => $productId]);
        }

        return $this->index($request);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->wishlistItems()->delete();

        return response()->json(['message' => 'Wishlist cleared.']);
    }
}
