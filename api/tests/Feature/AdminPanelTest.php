<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Widgets\LatestOrders;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_the_login_page_is_reachable(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_guests_are_redirected_away_from_the_panel(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_a_customer_account_cannot_open_the_panel(): void
    {
        // Storefront customers live in the same users table, so without the
        // is_admin check every registered shopper could reach /admin.
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_an_admin_sees_the_dashboard(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_latest_orders_widget_renders_a_customer_phone(): void
    {
        Order::create([
            'order_number' => 'CB-WIDGET-1',
            'customer_name' => 'Widget Customer',
            'phone' => '01712345678',
            'address' => 'Dhaka',
            'shipping_area' => 'inside',
            'subtotal' => 10000,
            'shipping_charge' => 7000,
            'discount' => 0,
            'total' => 17000,
        ]);

        Livewire::actingAs($this->admin())
            ->test(LatestOrders::class)
            ->assertOk()
            ->assertSee('01712345678');
    }

    #[DataProvider('resourcePages')]
    public function test_resource_pages_load(string $path): void
    {
        $this->actingAs($this->admin())->get($path)->assertOk();
    }

    public static function resourcePages(): array
    {
        return [
            'products list' => ['/admin/products'],
            'product create' => ['/admin/products/create'],
            'categories list' => ['/admin/categories'],
            'category create' => ['/admin/categories/create'],
            'orders list' => ['/admin/orders'],
            'coupons list' => ['/admin/coupons'],
            'contact messages' => ['/admin/contact-messages'],
        ];
    }

    public function test_orders_cannot_be_created_by_hand(): void
    {
        $this->actingAs($this->admin())->get('/admin/orders/create')->assertNotFound();
    }

    public function test_a_price_typed_in_taka_is_stored_as_poisha(): void
    {
        // The riskiest part of the panel: the form shows taka but the column
        // holds integer poisha. If this conversion ever breaks, every price
        // in the shop silently changes by a factor of 100.
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $category = \App\Models\Category::first();

        Livewire::actingAs($this->admin())
            ->test(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Poisha Conversion Test',
                'price' => 1499.50,
                'discount_percentage' => 10,
                'stock' => 7,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = \App\Models\Product::where('title', 'Poisha Conversion Test')->firstOrFail();

        $this->assertSame(149950, $product->price, 'Price should be stored in poisha.');
        $this->assertSame(134955, $product->discountedPrice());

        // And it must survive the round trip back into the edit form.
        Livewire::actingAs($this->admin())
            ->test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet(['price' => 1499.50]);
    }

    public function test_an_order_detail_page_loads(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);

        $order = app(\App\Services\OrderService::class)->place([
            'customer_name' => 'Nahid',
            'phone' => '01712345678',
            'address' => 'Dhaka',
            'shipping_area' => 'inside',
            'items' => [['id' => \App\Models\Product::first()->id, 'quantity' => 1]],
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/orders/{$order->order_number}")
            ->assertOk()
            ->assertSee('Nahid');
    }

    public function test_an_admin_can_create_a_variable_product(): void
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $category = \App\Models\Category::first();

        Livewire::actingAs($this->admin())
            ->test(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Admin Variable Shirt',
                'product_type' => 'variable',
                'variation_options' => [
                    ['name' => 'Color', 'values' => ['Black', 'Blue']],
                    ['name' => 'Size', 'values' => ['Large', 'Medium']],
                ],
                'variants' => [[
                    'name' => null,
                    'sku' => 'ADMIN-BLACK-L',
                    'attributes' => [
                        ['name' => 'Color', 'value' => 'Black'],
                        ['name' => 'Size', 'value' => 'Large'],
                    ],
                    'price' => 850,
                    'discount_percentage' => 5,
                    'stock' => 4,
                    'is_active' => true,
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = \App\Models\Product::where('title', 'Admin Variable Shirt')->firstOrFail();
        $variant = $product->variants()->firstOrFail();

        $this->assertSame('variable', $product->product_type);
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertSame(85000, $variant->price);
        $this->assertSame(['Color' => 'Black', 'Size' => 'Large'], $variant->attributes);
        $this->assertSame('Black / Large', $variant->name);
    }

    public function test_an_admin_can_configure_a_simple_wholesale_product_in_taka(): void
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $category = \App\Models\Category::first();

        Livewire::actingAs($this->admin())
            ->test(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Admin Wholesale Product',
                'price' => 100,
                'stock' => 100,
                'is_wholesale' => true,
                'wholesale_price' => 80,
                'wholesale_min_quantity' => 10,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = \App\Models\Product::where('title', 'Admin Wholesale Product')->firstOrFail();

        $this->assertTrue($product->is_wholesale);
        $this->assertSame(8000, $product->wholesale_price);
        $this->assertSame(10, $product->wholesale_min_quantity);

        Livewire::actingAs($this->admin())
            ->test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet([
                'wholesale_price' => 80,
                'wholesale_min_quantity' => 10,
            ]);
    }

    public function test_an_admin_can_set_a_wholesale_price_for_a_variation(): void
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $category = \App\Models\Category::first();

        Livewire::actingAs($this->admin())
            ->test(CreateProduct::class)
            ->fillForm([
                'category_id' => $category->id,
                'title' => 'Admin Variable Wholesale Product',
                'product_type' => 'variable',
                'is_wholesale' => true,
                'wholesale_min_quantity' => 5,
                'variation_options' => [
                    ['name' => 'Size', 'values' => ['Large']],
                ],
                'variants' => [[
                    'name' => 'Large',
                    'attributes' => [
                        ['name' => 'Size', 'value' => 'Large'],
                    ],
                    'price' => 120,
                    'wholesale_price' => 90,
                    'discount_percentage' => 0,
                    'stock' => 20,
                    'is_active' => true,
                ]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = \App\Models\Product::where('title', 'Admin Variable Wholesale Product')->firstOrFail();

        $this->assertSame(9000, $product->variants()->firstOrFail()->wholesale_price);
        $this->assertSame(9000, $product->fresh()->wholesale_price);
    }

    public function test_an_empty_variation_price_shows_validation_instead_of_crashing(): void
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);

        Livewire::actingAs($this->admin())
            ->test(CreateProduct::class)
            ->fillForm([
                'category_id' => \App\Models\Category::first()->id,
                'title' => 'Incomplete Variable Product',
                'product_type' => 'variable',
                'variation_options' => [
                    ['name' => 'Color', 'values' => ['Red']],
                ],
                'variants' => [[
                    'name' => 'Red',
                    'attributes' => [['name' => 'Color', 'value' => 'Red']],
                    'price' => null,
                    'stock' => 1,
                    'is_active' => true,
                ]],
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertDatabaseMissing('products', ['title' => 'Incomplete Variable Product']);
    }
}
