<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Product, 1: ProductVariant, 2: ProductVariant} */
    protected function variableProduct(): array
    {
        $category = Category::create(['name' => 'Clothing', 'slug' => 'clothing']);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'Classic Cotton Shirt',
            'product_type' => 'variable',
            'variation_options' => [
                ['name' => 'Color', 'values' => ['Red', 'Blue']],
                ['name' => 'Size', 'values' => ['Medium', 'Large']],
            ],
            'price' => 0,
            'stock' => 0,
        ]);

        $red = $product->variants()->create([
            'name' => 'Red / Medium',
            'sku' => 'SHIRT-RED-M',
            'attributes' => ['Color' => 'Red', 'Size' => 'Medium'],
            'price' => Money::toPoisha(1200),
            'discount_percentage' => 10,
            'stock' => 3,
            'position' => 1,
        ]);

        $blue = $product->variants()->create([
            'name' => 'Blue / Large',
            'sku' => 'SHIRT-BLUE-L',
            'attributes' => ['Color' => 'Blue', 'Size' => 'Large'],
            'price' => Money::toPoisha(1000),
            'stock' => 2,
            'position' => 2,
        ]);

        return [$product->fresh(), $red, $blue];
    }

    public function test_product_uses_a_readable_unique_slug(): void
    {
        [$product] = $this->variableProduct();

        $duplicate = Product::create([
            'category_id' => $product->category_id,
            'title' => 'Classic Cotton Shirt',
            'price' => 100,
        ]);

        $this->assertSame('classic-cotton-shirt', $product->slug);
        $this->assertSame('classic-cotton-shirt-2', $duplicate->slug);
    }

    public function test_product_can_be_loaded_by_slug_or_legacy_numeric_id(): void
    {
        [$product] = $this->variableProduct();

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('productType', 'variable')
            ->assertJsonCount(2, 'variants')
            ->assertJsonCount(2, 'variationAttributes')
            ->assertJsonPath('variationAttributes.0.name', 'Color');

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('slug', 'classic-cotton-shirt');
    }

    public function test_variable_product_summary_and_cart_use_variant_values(): void
    {
        [$product, $red] = $this->variableProduct();

        $this->assertSame(5, $product->stock);
        $this->assertSame(Money::toPoisha(1000), $product->price);

        $this->postJson('/api/cart/validate', [
            'items' => [['id' => $product->id, 'variant_id' => $red->id, 'quantity' => 2]],
        ])
            ->assertOk()
            ->assertJsonPath('items.0.variant.id', $red->id)
            ->assertJsonPath('items.0.unitPrice', 1080)
            ->assertJsonPath('items.0.lineTotal', 2160);
    }

    public function test_order_snapshots_and_decrements_the_selected_variant(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        [$product, $red] = $this->variableProduct();

        $this->postJson('/api/orders', [
            'customer_name' => 'Variation Buyer',
            'phone' => '01712345678',
            'address' => 'Dhaka',
            'shipping_area' => 'inside',
            'items' => [['id' => $product->id, 'variant_id' => $red->id, 'quantity' => 2]],
        ])
            ->assertCreated()
            ->assertJsonPath('order.items.0.variantId', $red->id)
            ->assertJsonPath('order.items.0.variantName', 'Red / Medium')
            ->assertJsonPath('order.items.0.variantAttributes.Color', 'Red');

        $this->assertSame(1, $red->fresh()->stock);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_variable_product_requires_a_valid_variation(): void
    {
        [$product] = $this->variableProduct();

        $this->postJson('/api/cart/validate', [
            'items' => [['id' => $product->id, 'quantity' => 1]],
        ])
            ->assertOk()
            ->assertJsonPath('issues.0.type', 'variation_required')
            ->assertJsonCount(0, 'items');
    }
}
