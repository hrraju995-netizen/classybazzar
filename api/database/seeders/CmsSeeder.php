<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'header' => ['Main navigation', [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'All Products', 'url' => '/products'],
                ['label' => 'Contact', 'url' => '/contact'],
                ['label' => 'About', 'url' => '/about'],
            ]],
            'footer_support' => ['Footer - Support', [
                ['label' => 'Order Tracking', 'url' => '/order/ordertrack'],
                ['label' => 'Contact Us', 'url' => '/contact'],
                ['label' => 'Return Policy', 'url' => '/return-policy'],
            ]],
            'footer_account' => ['Footer - Account', [
                ['label' => 'My Account', 'url' => '/user/profile'],
                ['label' => 'Login / Register', 'url' => '/login'],
                ['label' => 'Cart', 'url' => '/cart'],
                ['label' => 'Wishlist', 'url' => '/wishlist'],
            ]],
            'footer_quick' => ['Footer - Quick links', [
                ['label' => 'Privacy Policy', 'url' => '/privacy-policy'],
                ['label' => 'Terms of Service', 'url' => '/terms'],
                ['label' => 'FAQ', 'url' => '/faq'],
            ]],
        ];

        foreach ($menus as $location => [$name, $items]) {
            $menu = Menu::updateOrCreate(['location' => $location], ['name' => $name]);

            if ($menu->items()->exists()) {
                continue;
            }

            foreach ($items as $i => $item) {
                $menu->items()->create($item + ['sort_order' => $i]);
            }
        }

        $pages = [
            ['Privacy Policy', 'privacy-policy'],
            ['Terms of Service', 'terms'],
            ['Return Policy', 'return-policy'],
            ['FAQ', 'faq'],
        ];

        foreach ($pages as [$title, $slug]) {
            Page::updateOrCreate(['slug' => $slug], [
                'title' => $title,
                'content' => "<p>Edit this page from the admin panel under Pages.</p>",
                'is_published' => true,
            ]);
        }

        $defaults = [
            'theme.primary_color' => '#eb6e1b',
            'theme.secondary_color' => '#000000',
            'theme.button_color' => '#eb6e1b',
            'theme.button_text_color' => '#ffffff',
            'theme.header_background' => '#ffffff',
            'theme.header_text_color' => '#111827',
            'theme.topbar_background' => '#111111',
            'theme.topbar_text_color' => '#fafafa',
            'theme.footer_background' => '#050505',
            'theme.footer_text_color' => '#ffffff',
            'theme.body_background' => '#ffffff',
            'theme.font_heading' => 'Poppins',
            'theme.font_body' => 'Inter',
            'header.show_topbar' => true,
            'header.show_search' => true,
            'header.sticky' => true,
            'header.topbar_text' => 'Summer Sale For All Swim Suits And Free Express Delivery - OFF 50%!',
            'header.topbar_link_label' => 'Shop Now',
            'header.topbar_link_url' => '/products',
            'footer.about' => 'Classybazar - gadgets and accessories delivered across Bangladesh.',
            'footer.subscribe_title' => 'Subscribe',
            'footer.subscribe_text' => 'Get special offers and product updates.',
            'footer.copyright' => '© '.date('Y').' Classybazar. All rights reserved.',
            'footer.social' => [
                ['platform' => 'facebook', 'url' => 'https://facebook.com'],
                ['platform' => 'instagram', 'url' => 'https://instagram.com'],
            ],
            'contact.phone' => '+8801700000000',
            'contact.email' => 'support@classybazar.com',
            'contact.address' => 'Dhaka, Bangladesh',
            'home.slides' => [],
            'home.slider_autoplay' => true,
            'home.slider_delay' => 4500,
            'home.fixed_category_sidebar' => false,
            'home.category_ids' => [],
            'home.sections' => [
                ['type' => 'flash_sales', 'enabled' => true, 'eyebrow' => 'Today\'s', 'title' => 'Flash Sales', 'limit' => 8],
                ['type' => 'categories', 'enabled' => true, 'eyebrow' => 'Categories', 'title' => 'Browse By Category', 'limit' => 12],
                ['type' => 'best_selling', 'enabled' => true, 'eyebrow' => 'This Month', 'title' => 'Best Selling Products', 'limit' => 4],
                ['type' => 'promo', 'enabled' => true, 'eyebrow' => 'Featured offer', 'title' => '', 'limit' => 1],
                ['type' => 'products', 'enabled' => true, 'eyebrow' => 'Our Products', 'title' => 'Explore Our Products', 'limit' => 8],
                ['type' => 'featured', 'enabled' => true, 'eyebrow' => 'Featured', 'title' => 'New Arrival', 'limit' => 4],
                ['type' => 'support', 'enabled' => true, 'eyebrow' => '', 'title' => '', 'limit' => 3],
            ],
            'home.support_items' => [
                ['title' => 'FREE AND FAST DELIVERY', 'description' => 'Fast delivery across Bangladesh'],
                ['title' => '24/7 CUSTOMER SERVICE', 'description' => 'Friendly customer support whenever you need it'],
                ['title' => 'MONEY BACK GUARANTEE', 'description' => 'Simple returns and dependable purchase protection'],
            ],
            'courier.steadfast.base_url' => 'https://portal.packzy.com/api/v1',
            'courier.pathao.base_url' => 'https://api-hermes.pathao.com',
            'courier.pathao.delivery_type' => 48,
            'courier.pathao.default_weight' => 0.5,
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
