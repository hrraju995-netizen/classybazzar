<?php

namespace App\Http\Resources;

use App\Support\Media;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shaped to match the DummyJSON product payload the Next.js frontend was
 * built against, so swapping the API base URL is the only change needed
 * on the client. Extra fields are additive and safe to ignore.
 */
class ProductResource extends JsonResource
{
    /** Frontend expects bare objects/arrays, not a "data" envelope. */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $discounted = $this->discountedPrice();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'productType' => $this->product_type,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => $this->category->slug, null),
            'categoryName' => $this->whenLoaded('category', fn () => $this->category->name, null),
            'brand' => $this->brand,
            'sku' => $this->sku,

            // `price` is what the customer pays — discount already applied.
            'price' => Money::toTaka($discounted),
            'originalPrice' => Money::toTaka($this->price),
            'discountPercentage' => (float) $this->discount_percentage,
            'isWholesale' => (bool) $this->is_wholesale && filled($this->wholesale_price),
            'wholesalePrice' => filled($this->wholesale_price) ? Money::toTaka((int) $this->wholesale_price) : null,
            'wholesaleMinQuantity' => max(2, (int) $this->wholesale_min_quantity),

            'rating' => (float) $this->rating,
            'reviewsCount' => (int) $this->reviews_count,
            'stock' => (int) $this->stock,
            'availabilityStatus' => $this->stock > 0 ? 'In Stock' : 'Out of Stock',

            'thumbnail' => Media::url($this->thumbnail),
            'images' => Media::urls($this->images),

            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'variationAttributes' => $this->whenLoaded('variants', function () {
                $attributes = [];

                foreach ($this->variants as $variant) {
                    foreach (\App\Models\Product::normalizeVariantAttributes($variant->attributes ?? []) as $name => $value) {
                        if (blank($name) || blank($value)) {
                            continue;
                        }

                        $key = mb_strtolower($name);
                        $attributes[$key] ??= ['name' => $name, 'values' => []];
                        if (! collect($attributes[$key]['values'])->contains(fn ($existing) => mb_strtolower($existing) === mb_strtolower((string) $value))) {
                            $attributes[$key]['values'][] = (string) $value;
                        }
                    }
                }

                return collect($attributes)
                    ->values();
            }),

            'isFeatured' => (bool) $this->is_featured,
            'isFlashSale' => $this->isFlashSaleLive(),
            'flashSaleEndsAt' => $this->flash_sale_ends_at?->toIso8601String(),

            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
