<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * GET /api/products
     *
     * Returns the DummyJSON envelope { products, total, skip, limit } so the
     * existing frontend pages keep working after only a base-URL swap.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'skip' => ['nullable', 'integer', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
            'category' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'string', 'in:default,low-high,high-low,popularity,latest,best-selling'],
            'minPrice' => ['nullable', 'numeric', 'min:0'],
            'maxPrice' => ['nullable', 'numeric', 'min:0'],
            'featured' => ['nullable', 'boolean'],
            'flashSale' => ['nullable', 'boolean'],
        ]);

        $limit = (int) ($validated['limit'] ?? 30);
        $skip = isset($validated['page'])
            ? ((int) $validated['page'] - 1) * $limit
            : (int) ($validated['skip'] ?? 0);

        $query = Product::query()->with('category')->active();

        if (! empty($validated['category']) && $validated['category'] !== 'all') {
            $slug = $validated['category'];
            $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
        }

        $query->search($validated['q'] ?? null);

        // Filters arrive in taka but are stored in poisha. They are compared
        // against the *discounted* price, because that is the number shown on
        // the card — filtering on the pre-discount price would hide products
        // that visibly sit inside the customer's chosen range.
        $effectivePrice = 'price * (1 - discount_percentage / 100.0)';

        if (isset($validated['minPrice'])) {
            $query->whereRaw("$effectivePrice >= ?", [(int) round($validated['minPrice'] * 100)]);
        }

        if (isset($validated['maxPrice']) && $validated['maxPrice'] > 0) {
            $query->whereRaw("$effectivePrice <= ?", [(int) round($validated['maxPrice'] * 100)]);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->boolean('flashSale')) {
            $query->where('is_flash_sale', true)
                ->where(fn ($q) => $q->whereNull('flash_sale_ends_at')->orWhere('flash_sale_ends_at', '>', now()));
        }

        match ($validated['sort'] ?? 'default') {
            'low-high' => $query->orderByRaw('price * (1 - discount_percentage / 100.0) ASC'),
            'high-low' => $query->orderByRaw('price * (1 - discount_percentage / 100.0) DESC'),
            'popularity' => $query->orderByDesc('rating'),
            'best-selling' => $query->orderByDesc('sold_count'),
            'latest' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('id'),
        };

        $total = (clone $query)->toBase()->getCountForPagination();
        $products = $query->skip($skip)->take($limit)->get();

        return response()->json([
            'products' => ProductResource::collection($products),
            'total' => $total,
            'skip' => $skip,
            'limit' => $limit,
        ]);
    }

    /**
     * GET /api/products/{product}
     */
    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'variants' => fn ($q) => $q->active(),
            'reviews' => fn ($q) => $q->approved()->latest()->limit(20),
        ]);

        return new ProductResource($product);
    }

    /**
     * GET /api/products/{product}/related
     */
    public function related(Product $product, Request $request): JsonResponse
    {
        $limit = (int) $request->integer('limit', 8);

        $products = Product::query()
            ->with('category')
            ->active()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->inRandomOrder()
            ->limit(min($limit, 24))
            ->get();

        return response()->json(['products' => ProductResource::collection($products)]);
    }

    /**
     * GET /api/products/search?q=
     */
    public function search(Request $request): JsonResponse
    {
        $request->merge(['q' => $request->query('q', $request->query('query'))]);

        return $this->index($request);
    }
}
