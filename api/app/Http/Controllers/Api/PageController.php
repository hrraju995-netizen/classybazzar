<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'pages' => Page::published()->get(['title', 'slug'])->map(fn ($p) => [
                'title' => $p->title,
                'slug' => $p->slug,
            ]),
        ]);
    }

    public function show(Page $page): JsonResponse
    {
        abort_unless($page->is_published, 404);

        return response()->json([
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'metaTitle' => $page->meta_title ?: $page->title,
            'metaDescription' => $page->meta_description,
            'updatedAt' => $page->updated_at?->toIso8601String(),
        ]);
    }
}
