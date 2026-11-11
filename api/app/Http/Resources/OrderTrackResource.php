<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Services\OrderInvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matches the exact shape the /order/ordertrack page renders:
 * { id, status, date, eta, carrier, steps: [{ title, desc, time, completed, current }] }
 */
class OrderTrackResource extends JsonResource
{
    /** Frontend expects bare objects/arrays, not a "data" envelope. */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $pdfService = app(OrderInvoicePdfService::class);

        return [
            'id' => $this->order_number,
            'invoiceToken' => $pdfService->makeToken($this->resource),
            'invoiceUrl' => $pdfService->signedDownloadUrl($this->resource),
            'status' => $this->statusLabel(),
            'statusKey' => $this->status,
            'date' => $this->created_at?->format('F j, Y'),
            'eta' => $this->status === 'delivered'
                ? 'Delivered'
                : ($this->estimated_delivery_at?->format('F j, Y') ?? 'To be confirmed'),
            'carrier' => $this->carrier ?? 'Not assigned yet',
            'steps' => $this->buildSteps(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildSteps(): array
    {
        // Terminal states have no forward journey left to display.
        if (in_array($this->status, ['cancelled', 'returned'], true)) {
            return $this->events->map(fn ($event, $index) => [
                'title' => $event->title,
                'desc' => $event->description,
                'time' => $event->happened_at->format('h:i A'),
                'completed' => true,
                'current' => $index === $this->events->count() - 1,
            ])->all();
        }

        $reached = $this->events->keyBy('status');
        $currentIndex = array_search($this->status, Order::STATUS_FLOW, true);

        return collect(Order::STATUS_FLOW)
            ->map(function (string $status, int $index) use ($reached, $currentIndex) {
                $event = $reached->get($status);
                $completed = $currentIndex !== false && $index <= $currentIndex;

                return [
                    'title' => Order::STATUS_LABELS[$status],
                    'desc' => $event?->description ?? $this->defaultDescription($status),
                    'time' => $event?->happened_at->format('h:i A') ?? 'Pending',
                    'completed' => $completed,
                    'current' => $index === $currentIndex,
                ];
            })
            ->all();
    }

    protected function defaultDescription(string $status): string
    {
        return match ($status) {
            'pending' => 'We have received your order',
            'confirmed' => 'Seller has accepted the order',
            'processing' => 'Your parcel is being packed',
            'in_transit' => 'On the way to your city hub',
            'out_for_delivery' => 'Courier partner will deliver today',
            'delivered' => 'Successfully handed over to customer',
            default => '',
        };
    }
}
