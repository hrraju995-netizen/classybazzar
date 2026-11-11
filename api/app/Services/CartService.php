<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Validation\ValidationException;

/**
 * Prices a cart entirely from database values.
 *
 * The client never gets to decide what anything costs — it only sends
 * product ids and quantities. This is the single defence against a
 * tampered localStorage cart being checked out at an attacker's price.
 */
class CartService
{
    /**
     * @param  array<int, array{id: int, quantity: int, variant_id?: int|null}>  $items
     * @return array<string, mixed>
     */
    public function price(array $items, string $shippingArea = 'inside', ?string $couponCode = null): array
    {
        $quantities = [];
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $key = $id.':'.($variantId ?: 0);

            // Different variations stay as separate cart lines.
            $quantities[$key] ??= ['product_id' => $id, 'variant_id' => $variantId, 'quantity' => 0];
            $quantities[$key]['quantity'] += $qty;
        }

        $products = Product::query()
            ->with(['category', 'variants' => fn ($query) => $query->active()])
            ->whereIn('id', collect($quantities)->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        $lines = [];
        $issues = [];
        $subtotal = 0;

        foreach ($quantities as $item) {
            $id = $item['product_id'];
            $quantity = $item['quantity'];
            $product = $products->get($id);

            if (! $product || ! $product->is_active) {
                $issues[] = [
                    'productId' => $id,
                    'type' => 'unavailable',
                    'message' => 'This product is no longer available.',
                ];

                continue;
            }

            $variant = null;
            if ($product->isVariable()) {
                $variant = $product->variants->firstWhere('id', $item['variant_id']);

                if (! $variant) {
                    $issues[] = [
                        'productId' => $id,
                        'variantId' => $item['variant_id'],
                        'type' => 'variation_required',
                        'message' => "Choose an available variation of {$product->title}.",
                    ];

                    continue;
                }
            }

            $stock = $variant?->stock ?? $product->stock;

            if ($stock < 1) {
                $issues[] = [
                    'productId' => $id,
                    'variantId' => $variant?->id,
                    'type' => 'out_of_stock',
                    'message' => ($variant ? "{$product->title} ({$variant->name})" : $product->title).' is out of stock.',
                ];

                continue;
            }

            if ($stock < $quantity) {
                $issues[] = [
                    'productId' => $id,
                    'variantId' => $variant?->id,
                    'type' => 'insufficient_stock',
                    'available' => $stock,
                    'message' => "Only {$stock} unit(s) of {$product->title}".($variant ? " ({$variant->name})" : '').' left.',
                ];
                $quantity = $stock;
            }

            $retailUnitPrice = $variant?->discountedPrice() ?? $product->discountedPrice();
            $isWholesale = $product->usesWholesalePrice($quantity, $variant);
            $unitPrice = $product->priceForQuantity($quantity, $variant);
            $lineTotal = $unitPrice * $quantity;
            $subtotal += $lineTotal;

            $lines[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'retailUnitPrice' => $retailUnitPrice,
                'pricingType' => $isWholesale ? 'wholesale' : 'retail',
                'lineTotal' => $lineTotal,
            ];
        }

        $shipping = $this->shippingCharge($shippingArea, $subtotal);
        [$discount, $coupon] = $this->resolveCoupon($couponCode, $subtotal);

        return [
            'lines' => $lines,
            'issues' => $issues,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'coupon' => $coupon,
            'total' => max(0, $subtotal + $shipping - $discount),
        ];
    }

    public function shippingCharge(string $area, int $subtotal): int
    {
        $freeThreshold = (int) Setting::get('shipping.free_above', 0);

        if ($freeThreshold > 0 && $subtotal >= $freeThreshold) {
            return 0;
        }

        return $area === 'outside'
            ? (int) Setting::get('shipping.outside_dhaka', 13000)
            : (int) Setting::get('shipping.inside_dhaka', 7000);
    }

    /**
     * @return array{0: int, 1: ?Coupon}
     */
    protected function resolveCoupon(?string $code, int $subtotal): array
    {
        if (blank($code)) {
            return [0, null];
        }

        $coupon = Coupon::query()->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();

        if (! $coupon || ! $coupon->isRedeemable($subtotal)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon is invalid or has expired.',
            ]);
        }

        return [$coupon->discountFor($subtotal), $coupon];
    }
}
