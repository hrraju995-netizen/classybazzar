<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'attributes',
        'price',
        'wholesale_price',
        'discount_percentage',
        'stock',
        'image',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'price' => 'integer',
            'wholesale_price' => 'integer',
            'discount_percentage' => 'float',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProductVariant $variant) {
            $rawAttributes = $variant->getAttribute('attributes') ?? [];

            if (array_is_list($rawAttributes)) {
                $definitions = $variant->product?->variation_options ?? [];
                $rawAttributes = collect($definitions)
                    ->mapWithKeys(function (array $option, int $index) use ($rawAttributes) {
                        $value = $rawAttributes[$index] ?? null;
                        if (is_array($value)) {
                            $value = $value['value'] ?? null;
                        }

                        return filled($option['name'] ?? null) && filled($value)
                            ? [(string) $option['name'] => $value]
                            : [];
                    })
                    ->all();
            }

            $attributes = Product::normalizeVariantAttributes($rawAttributes);
            $variant->setAttribute('attributes', $attributes);

            if (blank($variant->name)) {
                $variant->name = implode(' / ', array_values($attributes)) ?: 'Variation';
            }
        });

        static::saved(fn (ProductVariant $variant) => $variant->product?->syncVariantSummary());
        static::deleted(fn (ProductVariant $variant) => $variant->product?->syncVariantSummary());
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function discountedPrice(): int
    {
        if ($this->discount_percentage <= 0) {
            return $this->price;
        }

        return (int) round($this->price * (1 - $this->discount_percentage / 100));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
