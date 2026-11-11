<?php

namespace App\Services\Couriers;

use App\Models\Order;
use App\Services\OrderService;

class CourierManager
{
    public function __construct(
        protected SteadfastCourier $steadfast,
        protected PathaoCourier $pathao,
        protected OrderService $orders,
    ) {}

    /** @return array<string, string> */
    public function available(): array
    {
        $options = [];

        if ($this->steadfast->isConfigured()) {
            $options[SteadfastCourier::NAME] = 'Steadfast';
        }

        if ($this->pathao->isConfigured()) {
            $options[PathaoCourier::NAME] = 'Pathao';
        }

        return $options;
    }

    public function driver(string $name): SteadfastCourier|PathaoCourier
    {
        return match ($name) {
            SteadfastCourier::NAME => $this->steadfast,
            PathaoCourier::NAME => $this->pathao,
            default => throw new CourierException("Unknown courier [{$name}]."),
        };
    }

    /**
     * Pushes the order to the courier and records the consignment on it.
     */
    public function send(Order $order, string $courier): CourierResult
    {
        if (filled($order->courier_consignment_id)) {
            throw new CourierException(
                "This order was already sent to {$order->courier} (consignment {$order->courier_consignment_id})."
            );
        }

        $result = $this->driver($courier)->createConsignment($order);

        $order->update([
            'courier' => $courier,
            'courier_consignment_id' => $result->consignmentId,
            'courier_response' => $result->raw,
            'carrier' => $courier === PathaoCourier::NAME ? 'Pathao' : 'Steadfast',
            'tracking_code' => $result->trackingCode,
        ]);

        // Moving to in_transit writes a timeline entry the customer can see.
        if (in_array($order->status, ['pending', 'confirmed', 'processing'], true)) {
            $this->orders->updateStatus(
                $order->fresh(['items', 'events']),
                'in_transit',
                'Handed over to '.($courier === PathaoCourier::NAME ? 'Pathao' : 'Steadfast'),
            );
        }

        return $result;
    }
}
