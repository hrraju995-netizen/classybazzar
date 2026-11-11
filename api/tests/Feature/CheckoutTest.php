<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function product(int $stock = 5): Product
    {
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        return Product::create([
            'category_id' => $category->id,
            'title' => 'Test Phone',
            'price' => Money::toPoisha(10000),
            'stock' => $stock,
        ]);
    }

    protected function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Nahid Hasan',
            'phone' => '01712345678',
            'address' => 'Ramnagar, Dhaka',
            'shipping_area' => 'inside',
            'items' => [['id' => $product->id, 'quantity' => 1]],
        ], $overrides);
    }

    public function test_a_guest_can_place_a_cash_on_delivery_order(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $product = $this->product();

        $this->postJson('/api/orders', $this->payload($product))
            ->assertCreated()
            ->assertJsonPath('order.totals.subtotal', 10000)
            ->assertJsonPath('order.totals.shipping', 70)
            ->assertJsonPath('order.totals.total', 10070);

        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_a_price_sent_by_the_client_is_ignored(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $product = $this->product();

        $payload = $this->payload($product);
        $payload['items'][0]['price'] = 1;
        $payload['items'][0]['discountPercentage'] = 99;

        $this->postJson('/api/orders', $payload)
            ->assertCreated()
            ->assertJsonPath('order.totals.subtotal', 10000);
    }

    public function test_quantity_is_clamped_to_available_stock(): void
    {
        $product = $this->product(stock: 2);

        $this->postJson('/api/cart/validate', [
            'items' => [['id' => $product->id, 'quantity' => 50]],
        ])
            ->assertOk()
            ->assertJsonPath('items.0.quantity', 2)
            ->assertJsonPath('issues.0.type', 'insufficient_stock');
    }

    public function test_an_out_of_stock_product_cannot_be_ordered(): void
    {
        $product = $this->product(stock: 0);

        $this->postJson('/api/orders', $this->payload($product))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_a_malformed_bangladeshi_phone_number_is_rejected(): void
    {
        $product = $this->product();

        $this->postJson('/api/orders', $this->payload($product, ['phone' => '12345']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_tracking_requires_the_phone_number_used_on_the_order(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $product = $this->product();

        $number = $this->postJson('/api/orders', $this->payload($product))
            ->json('order.orderNumber');

        $this->postJson('/api/orders/track', [
            'order_number' => $number,
            'phone' => '01712345678',
        ])->assertOk()->assertJsonStructure(['id', 'status', 'steps']);

        $this->postJson('/api/orders/track', [
            'order_number' => $number,
            'phone' => '01999999999',
        ])->assertNotFound();
    }
}
