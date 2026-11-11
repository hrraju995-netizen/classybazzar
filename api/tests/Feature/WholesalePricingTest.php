<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WholesalePricingTest extends TestCase
{
    use RefreshDatabase;

    protected function wholesaleProduct(array $overrides = []): Product
    {
        $category = Category::create([
            'name' => 'Wholesale',
            'slug' => 'wholesale',
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'title' => 'Wholesale Test Product',
            'price' => Money::toPoisha(100),
            'stock' => 100,
            'is_wholesale' => true,
            'wholesale_price' => Money::toPoisha(80),
            'wholesale_min_quantity' => 10,
        ], $overrides));
    }

    public function test_product_api_exposes_wholesale_offer(): void
    {
        $product = $this->wholesaleProduct();

        $this->getJson('/api/products/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('isWholesale', true)
            ->assertJsonPath('wholesalePrice', 80)
            ->assertJsonPath('wholesaleMinQuantity', 10);
    }

    public function test_cart_switches_to_wholesale_price_at_the_minimum_quantity(): void
    {
        $product = $this->wholesaleProduct();

        $this->postJson('/api/cart/validate', [
            'items' => [['id' => $product->id, 'quantity' => 9]],
        ])
            ->assertOk()
            ->assertJsonPath('items.0.pricingType', 'retail')
            ->assertJsonPath('items.0.unitPrice', 100)
            ->assertJsonPath('items.0.lineTotal', 900);

        $this->postJson('/api/cart/validate', [
            'items' => [['id' => $product->id, 'quantity' => 10]],
        ])
            ->assertOk()
            ->assertJsonPath('items.0.pricingType', 'wholesale')
            ->assertJsonPath('items.0.isWholesale', true)
            ->assertJsonPath('items.0.retailUnitPrice', 100)
            ->assertJsonPath('items.0.unitPrice', 80)
            ->assertJsonPath('items.0.lineTotal', 800);
    }

    public function test_order_ignores_client_price_and_snapshots_wholesale_sale_type(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $product = $this->wholesaleProduct();

        $this->postJson('/api/orders', [
            'customer_name' => 'Wholesale Buyer',
            'phone' => '01712345678',
            'address' => 'Dhaka',
            'shipping_area' => 'inside',
            'items' => [[
                'id' => $product->id,
                'quantity' => 10,
                'price' => 1,
                'wholesalePrice' => 1,
            ]],
        ])
            ->assertCreated()
            ->assertJsonPath('order.totals.subtotal', 800)
            ->assertJsonPath('order.items.0.pricingType', 'wholesale')
            ->assertJsonPath('order.items.0.isWholesale', true);

        $item = OrderItem::query()->firstOrFail();

        $this->assertSame(Money::toPoisha(80), $item->price);
        $this->assertSame('wholesale', $item->pricing_type);
    }

    public function test_variable_product_uses_the_selected_variation_wholesale_price(): void
    {
        $product = $this->wholesaleProduct([
            'title' => 'Variable Wholesale Product',
            'product_type' => 'variable',
            'price' => 0,
            'wholesale_price' => null,
            'stock' => 0,
            'wholesale_min_quantity' => 5,
        ]);

        $variant = $product->variants()->create([
            'name' => 'Large',
            'attributes' => ['Size' => 'Large'],
            'price' => Money::toPoisha(120),
            'wholesale_price' => Money::toPoisha(90),
            'stock' => 20,
        ]);

        $this->postJson('/api/cart/validate', [
            'items' => [[
                'id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 5,
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('items.0.pricingType', 'wholesale')
            ->assertJsonPath('items.0.unitPrice', 90)
            ->assertJsonPath('items.0.lineTotal', 450);
    }
}
