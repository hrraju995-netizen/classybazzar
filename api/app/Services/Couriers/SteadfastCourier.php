<?php

namespace App\Services\Couriers;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Facades\Http;

/**
 * Steadfast Courier (portal.packzy.com).
 *
 * Auth is two static headers - no OAuth, no token refresh - so a consignment
 * is a single POST.
 */
class SteadfastCourier
{
    public const NAME = 'steadfast';

    public function isConfigured(): bool
    {
        return filled(Setting::get('courier.steadfast.api_key'))
            && filled(Setting::get('courier.steadfast.secret_key'));
    }

    public function createConsignment(Order $order): CourierResult
    {
        if (! $this->isConfigured()) {
            throw new CourierException('Steadfast API credentials are not set. Add them under Settings.');
        }

        $response = Http::timeout(20)
            ->withHeaders([
                'Api-Key' => Setting::get('courier.steadfast.api_key'),
                'Secret-Key' => Setting::get('courier.steadfast.secret_key'),
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl().'/create_order', [
                'invoice' => $order->order_number,
                'recipient_name' => $order->customer_name,
                'recipient_phone' => $order->phone,
                'recipient_address' => $order->address,
                // Only collect cash when the customer has not already paid.
                'cod_amount' => $order->payment_status === 'paid'
                    ? 0
                    : Money::toTaka($order->total),
                'note' => $order->note ?? '',
            ]);

        if ($response->failed()) {
            throw new CourierException(
                'Steadfast rejected the request ('.$response->status().'): '.$response->body()
            );
        }

        $data = $response->json();

        if (($data['status'] ?? null) !== 200) {
            throw new CourierException($data['message'] ?? 'Steadfast returned an unexpected response.');
        }

        $consignment = $data['consignment'] ?? [];

        return new CourierResult(
            consignmentId: (string) ($consignment['consignment_id'] ?? ''),
            trackingCode: $consignment['tracking_code'] ?? null,
            raw: $data,
        );
    }

    /**
     * @return array{status: ?string, raw: array}
     */
    public function checkStatus(string $consignmentId): array
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'Api-Key' => Setting::get('courier.steadfast.api_key'),
                'Secret-Key' => Setting::get('courier.steadfast.secret_key'),
            ])
            ->get($this->baseUrl()."/status_by_cid/{$consignmentId}");

        $data = $response->json() ?? [];

        return [
            'status' => $data['delivery_status'] ?? null,
            'raw' => $data,
        ];
    }

    public function balance(): ?float
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'Api-Key' => Setting::get('courier.steadfast.api_key'),
                'Secret-Key' => Setting::get('courier.steadfast.secret_key'),
            ])
            ->get($this->baseUrl().'/get_balance');

        return $response->json('current_balance');
    }

    protected function baseUrl(): string
    {
        return rtrim(
            Setting::get('courier.steadfast.base_url', 'https://portal.packzy.com/api/v1'),
            '/'
        );
    }
}
