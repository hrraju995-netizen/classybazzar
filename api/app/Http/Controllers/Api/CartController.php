<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductVariantResource;
use App\Services\CartService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cart) {}

    /**
     * POST /api/cart/validate
     *
     * The client keeps its cart in localStorage. This endpoint re-prices that
     * cart against live database values, so the checkout page can show the
     * real total and warn about stock changes before an order is placed.
     */
    public function validateCart(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'max:100'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'shipping_area' => ['nullable', 'in:inside,outside'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ]);

        $priced = $this->cart->price(
            $data['items'],
            $data['shipping_area'] ?? 'inside',
            $data['coupon_code'] ?? null,
        );

        return response()->json([
            'items' => collect($priced['lines'])->map(fn ($line) => [
                'product' => new ProductResource($line['product']),
                'variant' => $line['variant'] ? new ProductVariantResource($line['variant']) : null,
                'quantity' => $line['quantity'],
                'unitPrice' => Money::toTaka($line['unitPrice']),
                'retailUnitPrice' => Money::toTaka($line['retailUnitPrice']),
                'pricingType' => $line['pricingType'],
                'isWholesale' => $line['pricingType'] === 'wholesale',
                'lineTotal' => Money::toTaka($line['lineTotal']),
            ])->values(),
            'issues' => $priced['issues'],
            'totals' => [
                'subtotal' => Money::toTaka($priced['subtotal']),
                'shipping' => Money::toTaka($priced['shipping']),
                'discount' => Money::toTaka($priced['discount']),
                'total' => Money::toTaka($priced['total']),
            ],
            'coupon' => $priced['coupon']?->only(['code', 'type', 'value']),
        ]);
    }
}
