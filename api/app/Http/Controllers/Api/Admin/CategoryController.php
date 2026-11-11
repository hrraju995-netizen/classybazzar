<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'categories' => CategoryResource::collection(
                Category::withCount('products')->orderBy('sort_order')->get()
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $category = Category::create($this->payload($request));

        return response()->json(['category' => new CategoryResource($category)], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $category->update($this->payload($request, $category));

        return response()->json(['category' => new CategoryResource($category->fresh())]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Move or delete this category\'s products first.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    protected function payload(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => [$category ? 'sometimes' : 'required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('categories', 'slug')->ignore($category)],
            'image' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
