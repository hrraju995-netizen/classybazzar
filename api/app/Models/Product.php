<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'product_type',
        'variation_options',
        'description',
        'brand',
        'sku',
        'price',
        'discount_percentage',
        'is_wholesale',
        'wholesale_price',
        'wholesale_min_quantity',
        'stock',
        'rating',
        'reviews_count',
        'sold_count',
        'thumbnail',
        'images',
        'is_active',
        'is_featured',
        'is_flash_sale',
        'flash_sale_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'variation_options' => 'array',
            'price' => 'integer',
            'wholesale_price' => 'integer',
            'wholesale_min_quantity' => 'integer',
            'discount_percentage' => 'float',
            'rating' => 'float',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_flash_sale' => 'boolean',
            'is_wholesale' => 'boolean',
            'flash_sale_ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if ($product->isVariable()) {
                $product->price ??= 0;
                $product->stock ??= 0;
                $product->discount_percentage ??= 0;
            }
        });

        static::saving(function (Product $product) {
            if ($product->isVariable()) {
                $product->variation_options = static::normalizeVariationOptions($product->variation_options ?? []);
            }

            if (blank($product->slug)) {
                $product->slug = $product->uniqueSlugFor($product->title);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    public function isVariable(): bool
    {
        return $this->product_type === 'variable';
    }

    public function syncVariantSummary(): void
    {
        if (! $this->isVariable()) {
            return;
        }

        $variants = $this->variants()->active()->get();
        $cheapest = $variants->sortBy(fn (ProductVariant $variant) => $variant->discountedPrice())->first();
        $cheapestWholesale = $variants
            ->filter(fn (ProductVariant $variant) => filled($variant->wholesale_price))
            ->sortBy('wholesale_price')
            ->first();

        $this->updateQuietly([
            'price' => $cheapest?->price ?? 0,
            'discount_percentage' => $cheapest?->discount_percentage ?? 0,
            'wholesale_price' => $cheapestWholesale?->wholesale_price,
            'stock' => $variants->sum('stock'),
        ]);
    }

    /** @param array<int, array{name?: mixed, values?: mixed}> $options */
    public static function normalizeVariationOptions(array $options): array
    {
        $groups = [];

        foreach ($options as $option) {
            $name = Str::headline(trim((string) ($option['name'] ?? '')));
            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);
            $groups[$key] ??= ['name' => $name, 'values' => []];

            foreach ((array) ($option['values'] ?? []) as $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                if (! collect($groups[$key]['values'])->contains(fn ($existing) => mb_strtolower($existing) === mb_strtolower($value))) {
                    $groups[$key]['values'][] = $value;
                }
            }
        }

        return array_values($groups);
    }

    /** @param array<string, mixed> $attributes */
    public static function normalizeVariantAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $name => $value) {
            $name = Str::headline(trim((string) $name));
            $value = trim((string) $value);

            if ($name !== '' && $value !== '') {
                $normalized[$name] = $value;
            }
        }

        return $normalized;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::query()
            ->where('slug', $value)
            ->when(ctype_digit((string) $value), fn (Builder $query) => $query->orWhere($this->getKeyName(), (int) $value))
            ->first();
    }

    protected function uniqueSlugFor(string $title): string
    {
        $base = Str::slug($title) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Selling price in poisha, after the discount percentage is applied.
     */
    public function discountedPrice(): int
    {
        if ($this->discount_percentage <= 0) {
            return $this->price;
        }

        return (int) round($this->price * (1 - $this->discount_percentage / 100));
    }

    public function wholesalePriceFor(?ProductVariant $variant = null): ?int
    {
        if (! $this->is_wholesale) {
            return null;
        }

        $price = $variant?->wholesale_price ?? $this->wholesale_price;

        return filled($price) && (int) $price > 0 ? (int) $price : null;
    }

    public function usesWholesalePrice(int $quantity, ?ProductVariant $variant = null): bool
    {
        return $quantity >= max(2, (int) $this->wholesale_min_quantity)
            && $this->wholesalePriceFor($variant) !== null;
    }

    public function priceForQuantity(int $quantity, ?ProductVariant $variant = null): int
    {
        if ($this->usesWholesalePrice($quantity, $variant)) {
            return (int) $this->wholesalePriceFor($variant);
        }

        return $variant?->discountedPrice() ?? $this->discountedPrice();
    }

    public function isFlashSaleLive(): bool
    {
        return $this->is_flash_sale
            && (is_null($this->flash_sale_ends_at) || $this->flash_sale_ends_at->isFuture());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', $term)
                ->orWhere('description', 'like', $term)
                ->orWhere('brand', 'like', $term);
        });
    }
}
