<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * GET /api/products/categories
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(CategoryResource::collection($categories));
    }

    /**
     * GET /api/products/category/{category}
     */
    public function products(Category $category, Request $request): JsonResponse
    {
        $request->merge(['category' => $category->slug]);

        return app(ProductController::class)->index($request);
    }
}
