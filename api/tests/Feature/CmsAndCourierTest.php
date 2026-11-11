<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Couriers\CourierException;
use App\Services\Couriers\CourierManager;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CmsAndCourierTest extends TestCase
{
    use RefreshDatabase;

    protected function order(): Order
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\CategorySeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);

        return app(OrderService::class)->place([
            'customer_name' => 'Nahid Hasan',
            'phone' => '01712345678',
            'address' => 'Ramnagar, Dhaka',
            'shipping_area' => 'inside',
            'items' => [['id' => Product::first()->id, 'quantity' => 1]],
        ]);
    }

    public function test_settings_expose_menus_and_theme_to_the_storefront(): void
    {
        $this->seed(\Database\Seeders\CmsSeeder::class);

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonStructure([
                'site' => ['name', 'logo'],
                'theme' => ['primaryColor', 'fontHeading'],
                'header' => ['showTopbar', 'topbarText'],
                'footer' => ['about', 'social'],
                'home' => ['slides', 'sliderAutoplay', 'sliderDelay', 'fixedCategorySidebar', 'sections', 'categoryIds', 'supportItems'],
                'menus',
            ])
            ->assertJsonCount(4, 'menus.header');
    }

    public function test_homepage_slides_and_appearance_are_exposed_to_the_storefront(): void
    {
        Setting::put('home.slides', [[
            'image' => 'homepage/slides/hero.jpg',
            'title' => 'Dashboard Hero',
            'button_label' => 'Browse now',
            'button_url' => '/products',
            'is_active' => true,
        ]]);
        Setting::put('theme.button_color', '#123456');
        Setting::put('home.category_ids', [3, 7]);
        Setting::put('home.fixed_category_sidebar', true);

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('home.slides.0.title', 'Dashboard Hero')
            ->assertJsonPath('home.slides.0.buttonLabel', 'Browse now')
            ->assertJsonPath('theme.buttonColor', '#123456')
            ->assertJsonPath('home.fixedCategorySidebar', true)
            ->assertJsonPath('home.categoryIds.0', 3)
            ->assertJsonPath('home.categoryIds.1', 7);
    }

    public function test_an_unset_pixel_id_is_never_sent_to_the_browser(): void
    {
        // A blank id would still inject the script tag, so it must be dropped
        // server side rather than checked in the component alone.
        $this->getJson('/api/settings')->assertOk()->assertJsonPath('tracking', []);

        Setting::put('tracking.fb_pixel_id', '123456');

        $this->getJson('/api/settings')->assertJsonPath('tracking.fbPixelId', '123456');
    }

    public function test_a_published_page_is_readable_and_a_draft_is_not(): void
    {
        Page::create(['title' => 'Refund Policy', 'slug' => 'refund', 'content' => '<p>Hi</p>']);
        Page::create(['title' => 'Secret', 'slug' => 'secret', 'is_published' => false]);

        $this->getJson('/api/pages/refund')->assertOk()->assertJsonPath('title', 'Refund Policy');
        $this->getJson('/api/pages/secret')->assertNotFound();
    }

    public function test_hidden_menu_items_are_not_published(): void
    {
        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);
        $menu->items()->create(['label' => 'Visible', 'url' => '/a']);
        $menu->items()->create(['label' => 'Hidden', 'url' => '/b', 'is_active' => false]);

        $this->getJson('/api/settings')->assertJsonCount(1, 'menus.header');
    }

    public function test_steadfast_receives_the_order_and_the_consignment_is_recorded(): void
    {
        Setting::put('courier.steadfast.api_key', 'key');
        Setting::put('courier.steadfast.secret_key', 'secret');

        Http::fake([
            '*/create_order' => Http::response([
                'status' => 200,
                'consignment' => ['consignment_id' => 99887, 'tracking_code' => 'SF123'],
            ]),
        ]);

        $order = $this->order();
        app(CourierManager::class)->send($order, 'steadfast');

        $order->refresh();
        $this->assertSame('steadfast', $order->courier);
        $this->assertSame('99887', $order->courier_consignment_id);
        $this->assertSame('SF123', $order->tracking_code);
        // Pushing to a courier should move the order along for the customer.
        $this->assertSame('in_transit', $order->status);
    }

    public function test_an_order_cannot_be_sent_to_a_courier_twice(): void
    {
        Setting::put('courier.steadfast.api_key', 'key');
        Setting::put('courier.steadfast.secret_key', 'secret');

        Http::fake(['*/create_order' => Http::response([
            'status' => 200,
            'consignment' => ['consignment_id' => 1, 'tracking_code' => 'X'],
        ])]);

        $order = $this->order();
        app(CourierManager::class)->send($order, 'steadfast');

        $this->expectException(CourierException::class);
        app(CourierManager::class)->send($order->fresh(), 'steadfast');
    }

    public function test_a_courier_without_credentials_is_not_offered(): void
    {
        $this->assertSame([], app(CourierManager::class)->available());

        Setting::put('courier.steadfast.api_key', 'key');
        Setting::put('courier.steadfast.secret_key', 'secret');

        $this->assertArrayHasKey('steadfast', app(CourierManager::class)->available());
    }

    public function test_a_courier_error_is_surfaced_rather_than_silently_swallowed(): void
    {
        Setting::put('courier.steadfast.api_key', 'key');
        Setting::put('courier.steadfast.secret_key', 'secret');

        Http::fake(['*/create_order' => Http::response(['status' => 400, 'message' => 'Invalid phone'], 200)]);

        $this->expectExceptionMessage('Invalid phone');
        app(CourierManager::class)->send($this->order(), 'steadfast');
    }

    public function test_the_settings_page_loads_for_an_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        foreach ([
            'branding' => 'Branding settings',
            'home-page' => 'Home page settings',
            'header' => 'Header settings',
            'footer' => 'Footer settings',
            'contact' => 'Contact settings',
            'shipping' => 'Shipping settings',
            'tracking' => 'Tracking settings',
            'couriers' => 'Couriers settings',
        ] as $section => $heading) {
            $this->get('/admin/manage-settings?section='.$section)
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('Live Preview');
        }

        Livewire::actingAs($admin)
            ->withQueryParams(['section' => 'branding'])
            ->test(ManageSettings::class)
            ->assertActionHasUrl('livePreview', 'http://localhost:3000')
            ->assertActionShouldOpenUrlInNewTab('livePreview');
    }

    public function test_live_preview_is_available_in_the_global_admin_header(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Live Preview')
            ->assertSee('http://localhost:3000')
            ->assertSee('_blank');
    }

    public function test_an_admin_can_save_homepage_and_appearance_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin);

        foreach ([
            'branding' => ['theme_button_color' => '#123456'],
            'home-page' => ['home_slider_delay' => 6500, 'home_fixed_category_sidebar' => true],
            'header' => ['header_show_search' => false],
            'footer' => ['footer_about' => 'Updated from the dashboard'],
        ] as $section => $state) {
            Livewire::withQueryParams(['section' => $section])
                ->test(ManageSettings::class)
                ->fillForm($state)
                ->call('save')
                ->assertHasNoFormErrors();
        }

        $this->assertSame('#123456', Setting::get('theme.button_color'));
        $this->assertSame(6500, Setting::get('home.slider_delay'));
        $this->assertTrue(Setting::get('home.fixed_category_sidebar'));
        $this->assertFalse(Setting::get('header.show_search'));
        $this->assertSame('Updated from the dashboard', Setting::get('footer.about'));
    }
}
