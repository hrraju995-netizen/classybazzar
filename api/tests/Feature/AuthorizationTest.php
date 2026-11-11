<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_wishlist_is_closed_to_guests(): void
    {
        $this->getJson('/api/wishlist')->assertUnauthorized();
    }

    public function test_a_customer_cannot_reach_the_admin_area(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }

    public function test_an_admin_can_reach_the_dashboard(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonStructure(['stats']);
    }

    public function test_a_customer_cannot_read_another_customers_order(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $category = \App\Models\Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $product = \App\Models\Product::create([
            'category_id' => $category->id,
            'title' => 'Test Phone',
            'price' => 1000000,
            'stock' => 5,
        ]);

        Sanctum::actingAs($owner);
        $number = $this->postJson('/api/orders', [
            'customer_name' => 'Owner',
            'phone' => '01712345678',
            'address' => 'Dhaka',
            'shipping_area' => 'inside',
            'items' => [['id' => $product->id, 'quantity' => 1]],
        ])->json('order.orderNumber');

        Sanctum::actingAs($stranger);
        $this->getJson("/api/orders/{$number}")->assertForbidden();
    }

    public function test_login_accepts_a_phone_number_as_the_identifier(): void
    {
        User::factory()->create([
            'phone' => '01911223344',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => '01911223344',
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }
}
