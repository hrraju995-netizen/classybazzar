<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorefrontCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.storefront.url' => 'http://localhost:3000',
            'services.storefront.revalidate_secret' => 'test-secret',
        ]);
    }

    public function test_saving_a_product_flushes_the_storefront_cache(): void
    {
        Http::fake();

        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'Cache Test',
            'price' => 100000,
            'stock' => 1,
        ]);

        Http::assertSent(function (Request $request) use ($product) {
            return $request->url() === 'http://localhost:3000/api/revalidate'
                && $request->header('x-revalidate-secret')[0] === 'test-secret'
                && in_array('products', $request['tags'], true)
                && in_array('product-'.$product->id, $request['tags'], true);
        });
    }

    public function test_a_storefront_that_is_down_does_not_break_an_admin_save(): void
    {
        // The shop owner must still be able to save products at 2am when the
        // Next.js dev server is not running.
        Http::fake(fn () => throw new \Exception('Connection refused'));

        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);

        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'Saved Anyway',
            'price' => 50000,
            'stock' => 2,
        ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'title' => 'Saved Anyway']);
    }

    public function test_nothing_is_sent_when_no_secret_is_configured(): void
    {
        config(['services.storefront.revalidate_secret' => null]);
        Http::fake();

        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        Product::create([
            'category_id' => $category->id,
            'title' => 'No Secret',
            'price' => 50000,
            'stock' => 1,
        ]);

        Http::assertNothingSent();
    }
}
