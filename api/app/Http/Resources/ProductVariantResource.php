<?php

namespace App\Http\Resources;

use App\Support\Media;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'attributes' => \App\Models\Product::normalizeVariantAttributes($this->attributes ?? []),
            'price' => Money::toTaka($this->discountedPrice()),
            'originalPrice' => Money::toTaka($this->price),
            'discountPercentage' => (float) $this->discount_percentage,
            'wholesalePrice' => filled($this->wholesale_price) ? Money::toTaka((int) $this->wholesale_price) : null,
            'stock' => (int) $this->stock,
            'availabilityStatus' => $this->stock > 0 ? 'In Stock' : 'Out of Stock',
            'image' => Media::url($this->image),
            'isActive' => (bool) $this->is_active,
        ];
    }
}
