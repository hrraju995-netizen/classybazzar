<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(protected CartService $cart) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function place(array $data, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $priced = $this->cart->price(
                $data['items'],
                $data['shipping_area'] ?? 'inside',
                $data['coupon_code'] ?? null,
            );

            if (empty($priced['lines'])) {
                throw ValidationException::withMessages([
                    'items' => 'None of the products in your cart are available right now.',
                ]);
            }

            if (! $userId && filled($data['phone'] ?? null)) {
                $customer = User::query()->where('phone', $data['phone'])->first();
                if (! $customer) {
                    $customer = User::create([
                        'name' => $data['customer_name'],
                        'phone' => $data['phone'],
                        'email' => null,
                        'address' => $data['address'] ?? null,
                        'password' => Str::random(16),
                        'auth_provider' => 'phone',
                    ]);
                } elseif (blank($customer->address) && filled($data['address'] ?? null)) {
                    $customer->update(['address' => $data['address']]);
                }
                $userId = $customer->id;
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $userId,
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'],
                'shipping_area' => $data['shipping_area'] ?? 'inside',
                'subtotal' => $priced['subtotal'],
                'shipping_charge' => $priced['shipping'],
                'discount' => $priced['discount'],
                'total' => $priced['total'],
                'coupon_code' => $priced['coupon']?->code,
                'payment_method' => $data['payment_method'] ?? 'cod',
                'payment_status' => 'pending',
                'payment_number' => $data['payment_number'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'status' => 'pending',
                'note' => $data['note'] ?? null,
                'estimated_delivery_at' => now()->addDays(
                    ($data['shipping_area'] ?? 'inside') === 'inside' ? 2 : 4
                ),
            ]);

            foreach ($priced['lines'] as $line) {
                /** @var Product $product */
                $product = $line['product'];
                /** @var ProductVariant|null $variant */
                $variant = $line['variant'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'title' => $product->title,
                    'variant_name' => $variant?->name,
                    'variant_sku' => $variant?->sku,
                    'variant_attributes' => $variant?->attributes,
                    'thumbnail' => $variant?->image ?: $product->thumbnail,
                    'price' => $line['unitPrice'],
                    'pricing_type' => $line['pricingType'],
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['lineTotal'],
                ]);

                // Conditional decrement: if two checkouts race for the last
                // unit, the second one gets 0 affected rows and is rejected.
                $affected = $variant
                    ? ProductVariant::query()
                        ->whereKey($variant->id)
                        ->where('stock', '>=', $line['quantity'])
                        ->decrement('stock', $line['quantity'])
                    : Product::query()
                        ->whereKey($product->id)
                        ->where('stock', '>=', $line['quantity'])
                        ->decrement('stock', $line['quantity']);

                if ($affected === 0) {
                    throw ValidationException::withMessages([
                        'items' => "{$product->title} just went out of stock. Please review your cart.",
                    ]);
                }

                if ($variant) {
                    $product->syncVariantSummary();
                }

                $product->increment('sold_count', $line['quantity']);
            }

            $priced['coupon']?->increment('used_count');

            $this->recordEvent($order, 'pending', 'We have received your order');

            $order = $order->load(['items', 'events']);

            try {
                app(OrderInvoicePdfService::class)->generateAndStore($order);
            } catch (\Throwable) {
                // Non-blocking if disk is read-only
            }

            return $order;
        });
    }

    public function updateStatus(Order $order, string $status, ?string $description = null): Order
    {
        if ($order->status === $status) {
            return $order;
        }

        // Returning stock on cancellation keeps the catalogue honest.
        if (in_array($status, ['cancelled', 'returned'], true)
            && ! in_array($order->status, ['cancelled', 'returned'], true)) {
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    if ($item->product_variant_id) {
                        ProductVariant::query()->whereKey($item->product_variant_id)->increment('stock', $item->quantity);
                        Product::find($item->product_id)?->syncVariantSummary();
                    } else {
                        Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
                    }
                }
            }
        }

        $order->update([
            'status' => $status,
            'payment_status' => $status === 'delivered' && $order->payment_method === 'cod'
                ? 'paid'
                : $order->payment_status,
        ]);

        $this->recordEvent($order, $status, $description);

        $freshOrder = $order->fresh(['items', 'events']);

        try {
            app(OrderInvoicePdfService::class)->generateAndStore($freshOrder);
        } catch (\Throwable) {
            // Non-blocking
        }

        return $freshOrder;
    }

    public function recordEvent(Order $order, string $status, ?string $description = null): OrderEvent
    {
        return $order->events()->create([
            'status' => $status,
            'title' => Order::STATUS_LABELS[$status] ?? $status,
            'description' => $description,
            'happened_at' => now(),
        ]);
    }

    protected function generateOrderNumber(): string
    {
        $prefix = Setting::get('order.prefix', 'CB');

        do {
            $number = $prefix.'-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
