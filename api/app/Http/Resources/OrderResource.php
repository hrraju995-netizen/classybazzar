<?php

namespace App\Http\Resources;

use App\Services\OrderInvoicePdfService;
use App\Support\Media;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /** Frontend expects bare objects/arrays, not a "data" envelope. */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $pdfService = app(OrderInvoicePdfService::class);
        $invoiceToken = $pdfService->makeToken($this->resource);

        return [
            'id' => $this->order_number,
            'orderNumber' => $this->order_number,
            'invoiceToken' => $invoiceToken,
            'invoiceUrl' => $pdfService->signedDownloadUrl($this->resource),
            'status' => $this->statusLabel(),
            'statusKey' => $this->status,
            'paymentMethod' => $this->payment_method,
            'paymentStatus' => $this->payment_status,

            'customer' => [
                'name' => $this->customer_name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'shippingArea' => $this->shipping_area,
            ],

            'totals' => [
                'subtotal' => Money::toTaka($this->subtotal),
                'shipping' => Money::toTaka($this->shipping_charge),
                'discount' => Money::toTaka($this->discount),
                'total' => Money::toTaka($this->total),
            ],

            'couponCode' => $this->coupon_code,
            'carrier' => $this->carrier,
            'trackingCode' => $this->tracking_code,
            'note' => $this->note,
            'date' => $this->created_at?->format('F j, Y'),
            'createdAt' => $this->created_at?->toIso8601String(),
            'isCancellable' => $this->isCancellable(),

            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'productId' => $item->product_id,
                'variantId' => $item->product_variant_id,
                'title' => $item->title,
                'variantName' => $item->variant_name,
                'variantSku' => $item->variant_sku,
                'variantAttributes' => $item->variant_attributes,
                'thumbnail' => Media::url($item->thumbnail),
                'price' => Money::toTaka($item->price),
                'pricingType' => $item->pricing_type,
                'isWholesale' => $item->pricing_type === 'wholesale',
                'quantity' => (int) $item->quantity,
                'subtotal' => Money::toTaka($item->subtotal),
            ])),
        ];
    }
}
