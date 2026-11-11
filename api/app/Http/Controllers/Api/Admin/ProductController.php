<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with('category')
            ->search($request->query('q'))
            ->when($request->filled('status'), function ($q) use ($request) {
                match ($request->query('status')) {
                    'inactive' => $q->where('is_active', false),
                    'out_of_stock' => $q->where('stock', 0),
                    'low_stock' => $q->whereBetween('stock', [1, 5]),
                    default => $q->where('is_active', true),
                };
            })
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json([
            'products' => ProductResource::collection($products->items()),
            'meta' => [
                'currentPage' => $products->currentPage(),
                'lastPage' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->payload($request));

        return response()->json([
            'product' => new ProductResource($product->load('category')),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'product' => new ProductResource($product->load('category')),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $product->update($this->payload($request, $product));

        return response()->json([
            'product' => new ProductResource($product->fresh('category')),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        // Soft-disable instead of deleting: order history references products.
        $product->update(['is_active' => false]);

        return response()->json(['message' => 'Product archived.']);
    }

    /**
     * POST /api/admin/products/images — multipart upload.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $path = $request->file('image')->store('products', 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(Request $request, ?Product $product = null): array
    {
        $sometimes = $product ? 'sometimes' : 'required';

        $data = $request->validate([
            'category_id' => [$sometimes, 'integer', 'exists:categories,id'],
            'title' => [$sometimes, 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('products', 'slug')->ignore($product)],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product)],
            'price' => [$sometimes, 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'is_wholesale' => ['nullable', 'boolean'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0.01'],
            'wholesale_min_quantity' => ['nullable', 'integer', 'min:2', 'max:10000'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'images' => ['nullable', 'array', 'max:12'],
            'images.*' => ['string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_flash_sale' => ['nullable', 'boolean'],
            'flash_sale_ends_at' => ['nullable', 'date'],
        ]);

        // Admins type prices in taka; storage is in poisha.
        if (isset($data['price'])) {
            $data['price'] = Money::toPoisha($data['price']);
        }

        if (isset($data['wholesale_price'])) {
            $data['wholesale_price'] = Money::toPoisha($data['wholesale_price']);
        }

        return $data;
    }
}
