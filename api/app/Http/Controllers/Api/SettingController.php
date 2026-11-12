<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Setting;
use App\Support\Media;
use App\Support\Money;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * GET /api/settings — everything the storefront needs to render its
     * chrome: branding, theme, menus, contact details and tracking ids.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'site' => [
                'name' => Setting::get('site.name', 'Classybazar'),
                'tagline' => Setting::get('site.tagline', 'Gadgets & Accessories Store'),
                'logo' => Media::url(Setting::get('site.logo') ?: 'branding/header-logo.png'),
                'footerLogo' => Media::url(Setting::get('site.footer_logo') ?: 'branding/footer-logo.png'),
                'favicon' => Media::url(Setting::get('site.favicon') ?: 'branding/favicon.png'),
                'metaTitle' => Setting::get('site.meta_title', 'Classybazar — Best Gadgets & Online Shopping in Bangladesh'),
                'metaDescription' => Setting::get('site.meta_description', 'Shop genuine gadgets, electronics, fashion, and lifestyle essentials at Classybazar with fast delivery across Bangladesh.'),
                'metaKeywords' => Setting::get('site.meta_keywords', 'classybazar, online shopping bangladesh, gadgets, electronics, fashion'),
            ],

            'theme' => [
                'primaryColor' => Setting::get('theme.primary_color', '#eb6e1b'),
                'secondaryColor' => Setting::get('theme.secondary_color', '#111827'),
                'buttonColor' => Setting::get('theme.button_color', '#eb6e1b'),
                'buttonHoverColor' => Setting::get('theme.button_hover_color', '#111827'),
                'buttonTextColor' => Setting::get('theme.button_text_color', '#ffffff'),
                'badgeColor' => Setting::get('theme.badge_color', '#ef4444'),
                'headerBackground' => Setting::get('theme.header_background', '#ffffff'),
                'headerTextColor' => Setting::get('theme.header_text_color', '#111827'),
                'menubarBackground' => Setting::get('theme.menubar_background', '#ffffff'),
                'menubarTextColor' => Setting::get('theme.menubar_text_color', '#111827'),
                'topbarBackground' => Setting::get('theme.topbar_background', '#111111'),
                'topbarTextColor' => Setting::get('theme.topbar_text_color', '#fafafa'),
                'footerBackground' => Setting::get('theme.footer_background', '#050505'),
                'footerTextColor' => Setting::get('theme.footer_text_color', '#ffffff'),
                'bodyBackground' => Setting::get('theme.body_background', '#ffffff'),
                'cardBackground' => Setting::get('theme.card_background', '#ffffff'),
                'textColor' => Setting::get('theme.text_color', '#111827'),
                'fontHeading' => Setting::get('theme.font_heading', 'Poppins'),
                'fontBody' => Setting::get('theme.font_body', 'Inter'),
            ],

            'header' => [
                'showTopbar' => (bool) Setting::get('header.show_topbar', true),
                'showSearch' => (bool) Setting::get('header.show_search', true),
                'sticky' => (bool) Setting::get('header.sticky', true),
                'topbarText' => Setting::get('header.topbar_text'),
                'topbarLinkLabel' => Setting::get('header.topbar_link_label'),
                'topbarLinkUrl' => Setting::get('header.topbar_link_url'),
            ],

            'footer' => [
                'about' => Setting::get('footer.about'),
                'copyright' => Setting::get('footer.copyright'),
                'subscribeTitle' => Setting::get('footer.subscribe_title', 'Subscribe'),
                'subscribeText' => Setting::get('footer.subscribe_text', 'Get special offers and product updates.'),
                'social' => Setting::get('footer.social', []),
            ],

            'home' => [
                'sliderEnabled' => (bool) Setting::get('home.slider_enabled', true),
                'slides' => $this->homeSlides(),
                'sliderAutoplay' => (bool) Setting::get('home.slider_autoplay', true),
                'sliderArrows' => (bool) Setting::get('home.slider_arrows', true),
                'sliderDelay' => (int) Setting::get('home.slider_delay', 4500),
                'fixedCategorySidebar' => (bool) Setting::get('home.fixed_category_sidebar', false),
                'categoryIds' => collect(Setting::get('home.category_ids', []))->map(fn($id) => (int) $id)->values(),
                'sections' => Setting::get('home.sections', $this->defaultHomeSections()),
                'supportItems' => Setting::get('home.support_items', $this->defaultSupportItems()),
            ],

            'contact' => [
                'phone' => Setting::get('contact.phone', '+880 1793-293600'),
                'email' => Setting::get('contact.email', 'support@classybazar.com'),
                'address' => Setting::get('contact.address', 'Dhaka, Bangladesh'),
                'workingHours' => Setting::get('contact.working_hours', 'Sat - Thu: 9:00 AM - 10:00 PM'),
                'whatsapp' => Setting::get('contact.whatsapp', '8801793293600'),
                'messenger' => Setting::get('contact.messenger', 'amarClassyBazar'),
                'showFloatingChat' => (bool) Setting::get('contact.show_floating_chat', true),
            ],

            'currency' => Setting::get('site.currency', 'BDT'),
            'currencySymbol' => Setting::get('site.currency_symbol', '৳'),

            'shipping' => [
                'inside' => Money::toTaka((int) Setting::get('shipping.inside_dhaka', 7000)),
                'outside' => Money::toTaka((int) Setting::get('shipping.outside_dhaka', 13000)),
                'freeAbove' => Money::toTaka((int) Setting::get('shipping.free_above', 0)),
            ],

            'paymentMethods' => [
                'cod' => [
                    'enabled' => (bool) Setting::get('payment.cod_enabled', true),
                    'title' => Setting::get('payment.cod_title', 'Cash on Delivery'),
                    'instructions' => Setting::get('payment.cod_instructions', 'Pay with cash upon delivery.'),
                ],
                'bkash' => [
                    'enabled' => (bool) Setting::get('payment.bkash_enabled', false),
                    'title' => Setting::get('payment.bkash_title', 'bKash'),
                    'number' => Setting::get('payment.bkash_number', ''),
                    'type' => Setting::get('payment.bkash_type', 'Personal'),
                    'instructions' => Setting::get('payment.bkash_instructions', 'Please Send Money to our bKash Personal number, then enter your bKash mobile number and Transaction ID (TrxID) below.'),
                ],
                'nagad' => [
                    'enabled' => (bool) Setting::get('payment.nagad_enabled', false),
                    'title' => Setting::get('payment.nagad_title', 'Nagad'),
                    'number' => Setting::get('payment.nagad_number', ''),
                    'type' => Setting::get('payment.nagad_type', 'Personal'),
                    'instructions' => Setting::get('payment.nagad_instructions', 'Please Send Money to our Nagad Personal number, then enter your Nagad mobile number and Transaction ID (TrxID) below.'),
                ],
                'rocket' => [
                    'enabled' => (bool) Setting::get('payment.rocket_enabled', false),
                    'title' => Setting::get('payment.rocket_title', 'Rocket'),
                    'number' => Setting::get('payment.rocket_number', ''),
                    'type' => Setting::get('payment.rocket_type', 'Personal'),
                    'instructions' => Setting::get('payment.rocket_instructions', 'Please Send Money to our Rocket Personal number, then enter your Rocket mobile number and Transaction ID (TrxID) below.'),
                ],
            ],

            'tracking' => array_filter([
                'fbPixelId' => Setting::get('tracking.fb_pixel_id'),
                'gaMeasurementId' => Setting::get('tracking.ga_measurement_id'),
                'gtmId' => Setting::get('tracking.gtm_id'),
                'tiktokPixelId' => Setting::get('tracking.tiktok_pixel_id'),
            ]),

            'menus' => $this->menus(),
        ]);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function menus(): array
    {
        return Menu::with(['rootItems' => fn($q) => $q->active(), 'rootItems.children' => fn($q) => $q->active()])
            ->get()
            ->mapWithKeys(fn(Menu $menu) => [
                $menu->location => $menu->rootItems->map(fn($item) => [
                    'label' => $item->label,
                    'url' => $item->url,
                    'newTab' => $item->open_in_new_tab,
                    'children' => $item->children->map(fn($child) => [
                        'label' => $child->label,
                        'url' => $child->url,
                        'newTab' => $child->open_in_new_tab,
                    ])->values(),
                ])->values(),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function defaultHomeSlides(): array
    {
        return [
            [
                'image' => 'homepage/slides/banner-1.jpg',
                'eyebrow' => 'Latest collection',
                'title' => 'Upgrade your everyday tech',
                'description' => 'Discover new arrivals and exclusive deals.',
                'button_label' => 'Shop Now',
                'button_url' => '/products',
                'secondary_button_label' => '',
                'secondary_button_url' => '',
                'text_align' => 'left',
                'overlay_opacity' => 75,
                'is_active' => true,
            ],
            [
                'image' => 'homepage/slides/banner-2.jpg',
                'eyebrow' => 'Member offer',
                'title' => 'Big savings on customer favourites',
                'description' => 'Limited-time prices across popular categories.',
                'button_label' => 'Explore Deals',
                'button_url' => '/products?sort=best-selling',
                'secondary_button_label' => '',
                'secondary_button_url' => '',
                'text_align' => 'left',
                'overlay_opacity' => 75,
                'is_active' => true,
            ],
            [
                'image' => 'homepage/slides/banner-3.jpg',
                'eyebrow' => 'Fresh arrivals',
                'title' => 'New products, ready to discover',
                'description' => 'Shop the newest additions to Classybazar.',
                'button_label' => 'View New Arrivals',
                'button_url' => '/products?sort=latest',
                'secondary_button_label' => '',
                'secondary_button_url' => '',
                'text_align' => 'left',
                'overlay_opacity' => 75,
                'is_active' => true,
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    protected function homeSlides(): array
    {
        $slides = Setting::get('home.slides');
        if (!is_array($slides) || count($slides) === 0) {
            $slides = self::defaultHomeSlides();
        }

        return collect($slides)
            ->filter(fn($slide) => is_array($slide) && ($slide['is_active'] ?? true) && filled($slide['image'] ?? null))
            ->map(fn(array $slide) => [
                'image' => Media::url($slide['image']),
                'eyebrow' => $slide['eyebrow'] ?? null,
                'title' => $slide['title'] ?? '',
                'description' => $slide['description'] ?? null,
                'buttonLabel' => $slide['button_label'] ?? 'Shop Now',
                'buttonUrl' => $slide['button_url'] ?? '/products',
                'secondaryButtonLabel' => $slide['secondary_button_label'] ?? null,
                'secondaryButtonUrl' => $slide['secondary_button_url'] ?? null,
                'textAlign' => $slide['text_align'] ?? 'left',
                'overlayOpacity' => isset($slide['overlay_opacity']) ? (int) $slide['overlay_opacity'] : 75,
                'eyebrowColor' => $slide['eyebrow_color'] ?? null,
                'titleColor' => $slide['title_color'] ?? null,
                'buttonColor' => $slide['button_color'] ?? null,
                'buttonTextColor' => $slide['button_text_color'] ?? null,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    protected function defaultHomeSections(): array
    {
        return [
            ['type' => 'flash_sales', 'enabled' => true, 'eyebrow' => 'Today\'s', 'title' => 'Flash Sales', 'limit' => 8],
            ['type' => 'categories', 'enabled' => true, 'eyebrow' => 'Categories', 'title' => 'Browse By Category', 'limit' => 12],
            ['type' => 'best_selling', 'enabled' => true, 'eyebrow' => 'This Month', 'title' => 'Best Selling Products', 'limit' => 4],
            ['type' => 'promo', 'enabled' => true, 'eyebrow' => 'Featured offer', 'title' => '', 'limit' => 1],
            ['type' => 'products', 'enabled' => true, 'eyebrow' => 'Our Products', 'title' => 'Explore Our Products', 'limit' => 8],
            ['type' => 'featured', 'enabled' => true, 'eyebrow' => 'Featured', 'title' => 'New Arrival', 'limit' => 4],
            ['type' => 'support', 'enabled' => true, 'eyebrow' => '', 'title' => '', 'limit' => 3],
        ];
    }

    /** @return array<int, array{title: string, description: string}> */
    protected function defaultSupportItems(): array
    {
        return [
            ['title' => 'FREE AND FAST DELIVERY', 'description' => 'Fast delivery across Bangladesh'],
            ['title' => '24/7 CUSTOMER SERVICE', 'description' => 'Friendly customer support whenever you need it'],
            ['title' => 'MONEY BACK GUARANTEE', 'description' => 'Simple returns and dependable purchase protection'],
        ];
    }
}
