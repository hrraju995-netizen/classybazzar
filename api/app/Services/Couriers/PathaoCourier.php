<?php

namespace App\Services\Couriers;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Pathao Courier merchant API.
 *
 * Unlike Steadfast this needs an OAuth access token, and Pathao addresses are
 * numeric city/zone/area ids rather than free text - so a default store, city
 * and zone must be configured before an order can be pushed.
 */
class PathaoCourier
{
    public const NAME = 'pathao';

    public function isConfigured(): bool
    {
        return filled(Setting::get('courier.pathao.client_id'))
            && filled(Setting::get('courier.pathao.client_secret'))
            && filled(Setting::get('courier.pathao.username'))
            && filled(Setting::get('courier.pathao.password'))
            && filled(Setting::get('courier.pathao.store_id'));
    }

    public function createConsignment(Order $order): CourierResult
    {
        if (! $this->isConfigured()) {
            throw new CourierException(
                'Pathao is not fully configured. Client id, secret, username, password and store id are all required.'
            );
        }

        $response = Http::timeout(25)
            ->withToken($this->accessToken())
            ->acceptJson()
            ->post($this->baseUrl().'/aladdin/api/v1/orders', [
                'store_id' => Setting::get('courier.pathao.store_id'),
                'merchant_order_id' => $order->order_number,
                'recipient_name' => $order->customer_name,
                'recipient_phone' => $order->phone,
                'recipient_address' => $order->address,
                'recipient_city' => (int) Setting::get('courier.pathao.city_id', 1),
                'recipient_zone' => (int) Setting::get('courier.pathao.zone_id', 1),
                'delivery_type' => (int) Setting::get('courier.pathao.delivery_type', 48),
                'item_type' => 2, // parcel, not document
                'item_quantity' => max(1, (int) $order->items()->sum('quantity')),
                'item_weight' => (float) Setting::get('courier.pathao.default_weight', 0.5),
                'amount_to_collect' => $order->payment_status === 'paid'
                    ? 0
                    : Money::toTaka($order->total),
                'item_description' => $order->items->pluck('title')->implode(', '),
                'special_instruction' => $order->note ?? '',
            ]);

        if ($response->failed()) {
            throw new CourierException(
                'Pathao rejected the request ('.$response->status().'): '.$response->body()
            );
        }

        $data = $response->json('data') ?? [];

        if (blank($data['consignment_id'] ?? null)) {
            throw new CourierException($response->json('message') ?? 'Pathao returned no consignment id.');
        }

        return new CourierResult(
            consignmentId: (string) $data['consignment_id'],
            trackingCode: $data['consignment_id'] ?? null,
            deliveryFee: isset($data['delivery_fee']) ? (float) $data['delivery_fee'] : null,
            raw: $response->json() ?? [],
        );
    }

    /**
     * Pathao tokens are long-lived; cache so every order push does not spend
     * an extra round trip re-authenticating.
     */
    public function accessToken(): string
    {
        return Cache::remember('pathao.access_token', now()->addHours(12), function () {
            $response = Http::timeout(20)
                ->acceptJson()
                ->post($this->baseUrl().'/aladdin/api/v1/issue-token', [
                    'client_id' => Setting::get('courier.pathao.client_id'),
                    'client_secret' => Setting::get('courier.pathao.client_secret'),
                    'grant_type' => 'password',
                    'username' => Setting::get('courier.pathao.username'),
                    'password' => Setting::get('courier.pathao.password'),
                ]);

            $token = $response->json('access_token');

            if (blank($token)) {
                throw new CourierException('Pathao authentication failed: '.$response->body());
            }

            return $token;
        });
    }

    /** @return array<int, array{id: int, name: string}> */
    public function cities(): array
    {
        return $this->lookup('/aladdin/api/v1/city-list', 'city_id', 'city_name');
    }

    /** @return array<int, array{id: int, name: string}> */
    public function zones(int $cityId): array
    {
        return $this->lookup("/aladdin/api/v1/cities/{$cityId}/zone-list", 'zone_id', 'zone_name');
    }

    protected function lookup(string $path, string $idKey, string $nameKey): array
    {
        $response = Http::timeout(20)->withToken($this->accessToken())->acceptJson()->get($this->baseUrl().$path);

        return collect($response->json('data.data') ?? [])
            ->map(fn ($row) => ['id' => $row[$idKey] ?? null, 'name' => $row[$nameKey] ?? ''])
            ->filter(fn ($row) => $row['id'] !== null)
            ->values()
            ->all();
    }

    protected function baseUrl(): string
    {
        // Sandbox and production are different hosts entirely.
        return rtrim(
            Setting::get('courier.pathao.base_url', 'https://api-hermes.pathao.com'),
            '/'
        );
    }
}
