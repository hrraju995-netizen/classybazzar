<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'title' => 'Test Phone',
            'price' => Money::toPoisha(10000),
            'discount_percentage' => 10,
            'stock' => 5,
        ], $overrides));
    }

    public function test_products_endpoint_returns_the_envelope_the_frontend_expects(): void
    {
        $this->makeProduct();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonStructure([
                'products' => [['id', 'title', 'price', 'thumbnail', 'images', 'stock', 'category']],
                'total', 'skip', 'limit',
            ]);
    }

    public function test_price_is_returned_in_taka_with_the_discount_already_applied(): void
    {
        $this->makeProduct();

        $this->getJson('/api/products')
            ->assertJsonPath('products.0.price', 9000)
            ->assertJsonPath('products.0.originalPrice', 10000);
    }

    public function test_price_filters_use_the_discounted_price_not_the_original(): void
    {
        // 10,000 tk listed, 9,000 tk after discount. A customer filtering for
        // "under 9,500" should see it even though the sticker price is higher.
        $this->makeProduct();

        $this->getJson('/api/products?maxPrice=9500')->assertJsonCount(1, 'products');
        $this->getJson('/api/products?maxPrice=8500')->assertJsonCount(0, 'products');
    }

    public function test_archived_products_are_hidden_from_the_storefront(): void
    {
        $product = $this->makeProduct(['is_active' => false]);

        $this->getJson("/api/products/{$product->id}")->assertNotFound();
        $this->getJson('/api/products')->assertJsonCount(0, 'products');
    }

    public function test_settings_expose_shipping_charges_in_taka(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('shipping.inside', 70)
            ->assertJsonPath('shipping.outside', 130);
    }
}
