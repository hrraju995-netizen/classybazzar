<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /** Frontend expects bare objects/arrays, not a "data" envelope. */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'image' => Media::url($this->image),
            'url' => url("/api/products/category/{$this->slug}"),
            'productsCount' => $this->when(isset($this->products_count), fn () => (int) $this->products_count),
        ];
    }
}
