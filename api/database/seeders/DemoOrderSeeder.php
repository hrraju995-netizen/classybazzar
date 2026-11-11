<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;

class DemoOrderSeeder extends Seeder
{
    public function run(OrderService $orders): void
    {
        $customer = User::query()->where('email', 'customer@classybazar.com')->first();
        $products = Product::query()->inStock()->inRandomOrder()->limit(4)->get();

        if (! $customer || $products->count() < 2) {
            return;
        }

        // Order 1 — already on its way, so the tracking page has live data.
        $inTransit = $orders->place([
            'customer_name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => 'House 12, Road 5, Dhanmondi, Dhaka',
            'shipping_area' => 'inside',
            'payment_method' => 'cod',
            'items' => [
                ['id' => $products[0]->id, 'quantity' => 1],
                ['id' => $products[1]->id, 'quantity' => 2],
            ],
        ], $customer->id);

        $inTransit->update(['carrier' => 'Pathao Courier']);
        $orders->updateStatus($inTransit, 'confirmed', 'Seller has accepted the order');
        $orders->updateStatus($inTransit, 'processing', 'Your parcel is being packed');
        $orders->updateStatus($inTransit, 'in_transit', 'On the way to your city hub');

        // Order 2 — completed, for the "delivered" branch of the same page.
        $delivered = $orders->place([
            'customer_name' => $customer->name,
            'phone' => $customer->phone,
            'address' => 'Flat B4, Uttara Sector 7, Dhaka',
            'shipping_area' => 'outside',
            'payment_method' => 'cod',
            'items' => [
                ['id' => $products[2]->id, 'quantity' => 1],
            ],
        ], $customer->id);

        $delivered->update(['carrier' => 'RedX']);
        foreach (['confirmed', 'processing', 'in_transit', 'out_for_delivery', 'delivered'] as $status) {
            $orders->updateStatus($delivered->fresh(['items', 'events']), $status);
        }

        $this->command?->info("Demo orders: {$inTransit->order_number} (in transit), {$delivered->order_number} (delivered)");
    }
}
