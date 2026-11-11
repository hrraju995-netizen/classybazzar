<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_can_be_created_with_a_main_image_and_a_gallery(): void
    {
        Storage::fake('public');

        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Upload Test Product',
                'price' => 999,
                'stock' => 3,
                'thumbnail' => UploadedFile::fake()->image('main.jpg'),
                'images' => [
                    UploadedFile::fake()->image('gallery-1.jpg'),
                    UploadedFile::fake()->image('gallery-2.jpg'),
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('title', 'Upload Test Product')->firstOrFail();

        $this->assertNotNull($product->thumbnail, 'Main image should be stored.');
        $this->assertCount(2, $product->images, 'Both gallery images should be stored.');

        Storage::disk('public')->assertExists($product->thumbnail);
        foreach ($product->images as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_an_uploaded_image_is_served_over_a_usable_url(): void
    {
        // The storefront reads `thumbnail` straight from the API, so a stored
        // path that does not resolve to a URL would render a broken image.
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'URL Test',
            'price' => 100000,
            'stock' => 1,
            'thumbnail' => 'products/example.jpg',
        ]);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('thumbnail', config('app.url').'/storage/products/example.jpg');
    }
}
