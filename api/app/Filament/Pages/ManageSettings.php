<?php

namespace App\Filament\Pages;

use App\Http\Controllers\Api\SettingController;
use App\Models\Category;
use App\Models\Setting;
use App\Services\StorefrontCache;
use App\Support\Media;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

use function Filament\Support\original_request;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    private const SLIDE_COUNT = 5;

    private const SETTINGS_SECTIONS = [
        'theme-settings' => ['label' => 'Theme & Colours', 'icon' => 'heroicon-o-paint-brush', 'sort' => 87],
        'logo-favicon' => ['label' => 'Logo & Favicon', 'icon' => 'heroicon-o-sparkles', 'sort' => 88],
        'branding' => ['label' => 'Store Identity & SEO', 'icon' => 'heroicon-o-building-storefront', 'sort' => 89],
        'hero-slider' => ['label' => 'Hero Slider', 'icon' => 'heroicon-o-photo', 'sort' => 90],
        'home-page' => ['label' => 'Home Page', 'icon' => 'heroicon-o-home', 'sort' => 91],
        'header' => ['label' => 'Header & Topbar', 'icon' => 'heroicon-o-bars-3', 'sort' => 92],
        'footer' => ['label' => 'Footer & Social', 'icon' => 'heroicon-o-bars-3-bottom-left', 'sort' => 93],
        'contact' => ['label' => 'Contact & Live Chat', 'icon' => 'heroicon-o-chat-bubble-left-right', 'sort' => 94],
        'shipping' => ['label' => 'Shipping & Delivery', 'icon' => 'heroicon-o-truck', 'sort' => 95],
        'tracking' => ['label' => 'Pixels & Tracking', 'icon' => 'heroicon-o-chart-bar', 'sort' => 96],
        'couriers' => ['label' => 'Courier API', 'icon' => 'heroicon-o-paper-airplane', 'sort' => 97],
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?string $title = 'Site settings';
    protected static ?int $navigationSort = 90;
    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];
    public string $section = 'branding';

    /** @return array<NavigationItem> */
    public static function getNavigationItems(): array
    {
        $items = [];

        foreach (self::SETTINGS_SECTIONS as $section => $configuration) {
            $items[] = NavigationItem::make($configuration['label'])
                ->key(static::class . '.' . $section)
                ->group('Site settings')
                ->icon($configuration['icon'])
                ->sort($configuration['sort'])
                ->url(static::getUrl(['section' => $section]))
                ->isActiveWhen(fn(): bool => original_request()->routeIs(static::getRouteName())
                    && original_request()->query('section', 'branding') === $section);
        }

        return $items;
    }

    public function getTitle(): string
    {
        return (self::SETTINGS_SECTIONS[$this->section]['label'] ?? 'Site') . ' settings';
    }

    /** @return array<string, mixed> */
    protected function definitions(): array
    {
        return [
            'site.name' => 'Classybazar',
            'site.tagline' => 'Gadgets & Accessories Store',
            'site.logo' => 'branding/header-logo.png',
            'site.footer_logo' => 'branding/footer-logo.png',
            'site.favicon' => 'branding/favicon.png',
            'site.meta_title' => 'Classybazar — Best Gadgets & Online Shopping in Bangladesh',
            'site.meta_description' => 'Shop genuine gadgets, electronics, fashion, and lifestyle essentials at Classybazar with fast delivery across Bangladesh.',
            'site.meta_keywords' => 'classybazar, online shopping bangladesh, gadgets, electronics, fashion',
            'site.currency' => 'BDT',
            'site.currency_symbol' => '৳',
            'order.prefix' => 'CB',

            'theme.primary_color' => '#eb6e1b',
            'theme.secondary_color' => '#111827',
            'theme.button_color' => '#eb6e1b',
            'theme.button_hover_color' => '#111827',
            'theme.button_text_color' => '#ffffff',
            'theme.badge_color' => '#ef4444',
            'theme.header_background' => '#ffffff',
            'theme.header_text_color' => '#111827',
            'theme.menubar_background' => '#ffffff',
            'theme.menubar_text_color' => '#111827',
            'theme.topbar_background' => '#111111',
            'theme.topbar_text_color' => '#fafafa',
            'theme.footer_background' => '#050505',
            'theme.footer_text_color' => '#ffffff',
            'theme.body_background' => '#ffffff',
            'theme.card_background' => '#ffffff',
            'theme.text_color' => '#111827',
            'theme.font_heading' => 'Poppins',
            'theme.font_body' => 'Inter',

            'header.show_topbar' => true,
            'header.show_search' => true,
            'header.sticky' => true,
            'header.topbar_text' => 'Summer Sale For All Items And Free Express Delivery - OFF 50%!',
            'header.topbar_link_label' => 'Shop Now',
            'header.topbar_link_url' => '/products',

            'footer.about' => 'Your trusted online destination for authentic gadgets, electronics, and lifestyle accessories across Bangladesh.',
            'footer.copyright' => '© ' . date('Y') . ' Classybazar. All rights reserved.',
            'footer.subscribe_title' => 'Subscribe',
            'footer.subscribe_text' => 'Get special offers and product updates.',
            'footer.social' => [
                ['platform' => 'facebook', 'url' => 'https://facebook.com'],
                ['platform' => 'instagram', 'url' => 'https://instagram.com'],
                ['platform' => 'youtube', 'url' => 'https://youtube.com'],
            ],

            'home.slider_enabled' => true,
            'home.slides' => SettingController::defaultHomeSlides(),
            'home.slider_autoplay' => true,
            'home.slider_arrows' => true,
            'home.slider_delay' => 4500,
            'home.fixed_category_sidebar' => false,
            'home.category_ids' => [],
            'home.sections' => $this->defaultHomeSections(),
            'home.support_items' => $this->defaultSupportItems(),

            'contact.phone' => '+880 1793-293600',
            'contact.email' => 'support@classybazar.com',
            'contact.address' => 'Dhaka, Bangladesh',
            'contact.working_hours' => 'Sat - Thu: 9:00 AM - 10:00 PM',
            'contact.whatsapp' => '8801793293600',
            'contact.messenger' => 'amarClassyBazar',
            'contact.show_floating_chat' => true,

            'tracking.fb_pixel_id' => '',
            'tracking.ga_measurement_id' => '',
            'tracking.gtm_id' => '',
            'tracking.tiktok_pixel_id' => '',

            'shipping.inside_dhaka' => 7000,
            'shipping.outside_dhaka' => 13000,
            'shipping.free_above' => 0,

            'courier.steadfast.api_key' => '',
            'courier.steadfast.secret_key' => '',
            'courier.steadfast.base_url' => 'https://portal.packzy.com/api/v1',
            'courier.pathao.base_url' => 'https://api-hermes.pathao.com',
            'courier.pathao.client_id' => '',
            'courier.pathao.client_secret' => '',
            'courier.pathao.username' => '',
            'courier.pathao.password' => '',
            'courier.pathao.store_id' => '',
            'courier.pathao.city_id' => '',
            'courier.pathao.zone_id' => '',
            'courier.pathao.delivery_type' => 48,
            'courier.pathao.default_weight' => 0.5,
        ];
    }

    public function mount(): void
    {
        $requestedSection = request()->query('section');

        if (is_string($requestedSection) && array_key_exists($requestedSection, self::SETTINGS_SECTIONS)) {
            $this->section = $requestedSection;
        }

        $data = [];

        foreach ($this->definitions() as $key => $default) {
            $val = Setting::get($key, $default);
            if ($key === 'home.slides' && (!is_array($val) || count($val) === 0)) {
                $val = SettingController::defaultHomeSlides();
                Setting::put('home.slides', $val);
            }
            if (in_array($key, ['site.logo', 'site.footer_logo', 'site.favicon'], true) && blank($val)) {
                $val = $default;
                Setting::put($key, $val);
            }
            $data[$this->fieldName($key)] = $val;
        }

        $data['site_logo_upload'] = null;
        $data['site_footer_logo_upload'] = null;
        $data['site_favicon_upload'] = null;
        $data['theme_preset'] = '';

        // Hydrate separate slide_1 .. slide_5 fields so each slide has its own independent inputs & uploader
        $slides = is_array($data['home_slides'] ?? null) ? array_values($data['home_slides']) : SettingController::defaultHomeSlides();
        $data = array_merge($data, $this->unpackSlidesToFields($slides));

        foreach (['shipping_inside_dhaka', 'shipping_outside_dhaka', 'shipping_free_above'] as $field) {
            $data[$field] = Money::toTaka((int) $data[$field]);
        }

        $this->form->fill($data);
    }

    /**
     * @param  array<int, array<string, mixed>>  $slides
     * @return array<string, mixed>
     */
    protected function unpackSlidesToFields(array $slides): array
    {
        $fields = [];

        for ($i = 1; $i <= self::SLIDE_COUNT; $i++) {
            $slide = $slides[$i - 1] ?? null;
            $fields["slide_{$i}_is_active"] = $slide ? (bool) ($slide['is_active'] ?? true) : false;
            $fields["slide_{$i}_image"] = $slide['image'] ?? null;
            $fields["slide_{$i}_upload"] = null;
            $fields["slide_{$i}_eyebrow"] = $slide['eyebrow'] ?? '';
            $fields["slide_{$i}_title"] = $slide['title'] ?? '';
            $fields["slide_{$i}_description"] = $slide['description'] ?? '';
            $fields["slide_{$i}_button_label"] = $slide['button_label'] ?? 'Shop Now';
            $fields["slide_{$i}_button_url"] = $slide['button_url'] ?? '/products';
            $fields["slide_{$i}_secondary_button_label"] = $slide['secondary_button_label'] ?? '';
            $fields["slide_{$i}_secondary_button_url"] = $slide['secondary_button_url'] ?? '';
            $fields["slide_{$i}_text_align"] = $slide['text_align'] ?? 'left';
            $fields["slide_{$i}_overlay_opacity"] = $slide['overlay_opacity'] ?? 75;
            $fields["slide_{$i}_eyebrow_color"] = $slide['eyebrow_color'] ?? null;
            $fields["slide_{$i}_title_color"] = $slide['title_color'] ?? null;
            $fields["slide_{$i}_button_color"] = $slide['button_color'] ?? null;
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, array<string, mixed>>
     */
    protected function packFieldsToSlides(array $state): array
    {
        $slides = [];

        for ($i = 1; $i <= self::SLIDE_COUNT; $i++) {
            $uploaded = $state["slide_{$i}_upload"] ?? null;
            if (is_array($uploaded)) {
                $uploaded = reset($uploaded) ?: null;
            }

            $image = filled($uploaded)
                ? (string) $uploaded
                : ($state["slide_{$i}_image"] ?? ($this->data["slide_{$i}_image"] ?? null));

            $title = trim((string) ($state["slide_{$i}_title"] ?? ''));
            $isActive = (bool) ($state["slide_{$i}_is_active"] ?? false);

            // Keep slide if it has an image or a title
            if (blank($image) && $title === '' && !$isActive) {
                continue;
            }

            $slides[] = [
                'image' => $image,
                'is_active' => $isActive,
                'eyebrow' => $state["slide_{$i}_eyebrow"] ?? '',
                'title' => $title !== '' ? $title : "Slide {$i}",
                'description' => $state["slide_{$i}_description"] ?? '',
                'button_label' => $state["slide_{$i}_button_label"] ?? 'Shop Now',
                'button_url' => $state["slide_{$i}_button_url"] ?? '/products',
                'secondary_button_label' => $state["slide_{$i}_secondary_button_label"] ?? '',
                'secondary_button_url' => $state["slide_{$i}_secondary_button_url"] ?? '',
                'text_align' => $state["slide_{$i}_text_align"] ?? 'left',
                'overlay_opacity' => (int) ($state["slide_{$i}_overlay_opacity"] ?? 75),
                'eyebrow_color' => $state["slide_{$i}_eyebrow_color"] ?? null,
                'title_color' => $state["slide_{$i}_title_color"] ?? null,
                'button_color' => $state["slide_{$i}_button_color"] ?? null,
            ];
        }

        return $slides;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->settingsTab()->getDefaultChildComponents())
            ->statePath('data');
    }

    protected function settingsTab(): Tab
    {
        return match ($this->section) {
            'theme-settings' => $this->themeSettingsTab(),
            'logo-favicon' => $this->logoFaviconTab(),
            'hero-slider' => $this->heroSliderTab(),
            'home-page' => $this->homePageTab(),
            'header' => $this->headerTab(),
            'footer' => $this->footerTab(),
            'contact' => $this->contactTab(),
            'shipping' => $this->shippingTab(),
            'tracking' => $this->trackingTab(),
            'couriers' => $this->couriersTab(),
            default => $this->brandingTab(),
        };
    }

    public function save(): void
    {
        $state = $this->form->getState();

        if ($this->section === 'hero-slider') {
            $slides = $this->packFieldsToSlides($state);
            Setting::put('home.slides', $slides);

            foreach ($this->unpackSlidesToFields($slides) as $k => $v) {
                $this->data[$k] = $v;
            }
        }

        if (in_array($this->section, ['branding', 'logo-favicon'], true)) {
            foreach ([
                'site_logo' => 'site_logo_upload',
                'site_footer_logo' => 'site_footer_logo_upload',
                'site_favicon' => 'site_favicon_upload',
            ] as $targetField => $uploadField) {
                $uploaded = $state[$uploadField] ?? null;
                if (is_array($uploaded)) {
                    $uploaded = reset($uploaded) ?: null;
                }
                if (filled($uploaded)) {
                    $state[$targetField] = (string) $uploaded;
                    $this->data[$targetField] = (string) $uploaded;
                    $this->data[$uploadField] = null;
                }
            }
        }

        foreach ($this->definitions() as $key => $default) {
            if ($key === 'home.slides') {
                continue;
            }

            $field = $this->fieldName($key);
            if (!array_key_exists($field, $state)) {
                continue;
            }

            $value = $state[$field];
            if (str_starts_with($key, 'shipping.')) {
                $value = Money::toPoisha($value ?? 0);
            }

            Setting::put($key, $value);
        }

        app(StorefrontCache::class)->flush(['settings', 'home', 'products', 'categories', 'pages']);
        Notification::make()->success()->title('Settings saved & storefront updated!')->send();
    }

    public function resetDefaultBranding(): void
    {
        $defaults = $this->definitions();
        foreach ($defaults as $key => $val) {
            if (str_starts_with($key, 'site.') || str_starts_with($key, 'theme.') || $key === 'order.prefix') {
                Setting::put($key, $val);
                $this->data[$this->fieldName($key)] = $val;
            }
        }
        $this->data['site_logo_upload'] = null;
        $this->data['site_footer_logo_upload'] = null;
        $this->data['site_favicon_upload'] = null;

        app(StorefrontCache::class)->flush(['settings', 'home']);
        Notification::make()->success()->title('Default branding, logos & colors restored!')->send();
    }

    public function resetDefaultSlides(): void
    {
        $defaults = SettingController::defaultHomeSlides();
        Setting::put('home.slides', $defaults);
        foreach ($this->unpackSlidesToFields($defaults) as $k => $v) {
            $this->data[$k] = $v;
        }
        app(StorefrontCache::class)->flush(['settings', 'home']);
        Notification::make()->success()->title('Default hero slides restored!')->send();
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if ($this->section === 'hero-slider') {
            $actions[] = Action::make('resetSlides')
                ->label('Restore default slides')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('This will replace current slides with the 3 default demo slides.')
                ->action('resetDefaultSlides');
        }

        if (in_array($this->section, ['branding', 'theme-settings', 'logo-favicon'], true)) {
            $actions[] = Action::make('resetBranding')
                ->label('Restore defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('This will reset logos, favicon, and theme colours back to the default Classybazar branding.')
                ->action('resetDefaultBranding');
        }

        $actions[] = Action::make('livePreview')
            ->label('Live Preview')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('gray')
            ->url(fn(): string => rtrim(config('services.storefront.url'), '/'))
            ->openUrlInNewTab();

        $actions[] = Action::make('save')
            ->label('Save changes')
            ->icon('heroicon-o-check')
            ->action('save');

        return $actions;
    }

    protected function fieldName(string $key): string
    {
        return str_replace('.', '_', $key);
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

    protected function homeSectionLabel(string $type): string
    {
        return match ($type) {
            'flash_sales' => 'Flash sales',
            'categories' => 'Categories',
            'best_selling' => 'Best selling products',
            'promo' => 'Featured promotion',
            'products' => 'Explore products',
            'featured' => 'New arrivals',
            'support' => 'Service highlights',
            default => 'Homepage section',
        };
    }

    protected function fonts(): array
    {
        return array_combine(
            $fonts = ['Poppins', 'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Nunito', 'Hind Siliguri', 'Noto Sans Bengali'],
            $fonts,
        );
    }

    /**
     * Builds the global slider settings card + 5 separate, independent Slide cards
     * so each slide's image and content can be changed individually without repeater lockups.
     *
     * @return array<int, Section>
     */
    protected function heroSliderSections(): array
    {
        $sections = [
            Section::make('Hero slider global settings')
                ->description('Control overall slider behaviour on the homepage.')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columns(3)
                ->schema([
                    Toggle::make('home_slider_enabled')
                        ->label('Show hero slider on homepage')
                        ->default(true),

                    Toggle::make('home_slider_autoplay')
                        ->label('Autoplay slides')
                        ->default(true),

                    Toggle::make('home_slider_arrows')
                        ->label('Show Next / Prev arrows')
                        ->default(true),

                    TextInput::make('home_slider_delay')
                        ->label('Slide duration')
                        ->suffix('ms')
                        ->prefixIcon('heroicon-o-clock')
                        ->numeric()
                        ->minValue(1500)
                        ->maxValue(20000)
                        ->default(4500),

                    Toggle::make('home_fixed_category_sidebar')
                        ->label('Keep category sidebar open beside slider')
                        ->helperText('Desktop: places the Browse Categories list on the left of the hero banner.')
                        ->default(false)
                        ->columnSpan(2),
                ]),
        ];

        for ($i = 1; $i <= self::SLIDE_COUNT; $i++) {
            $sections[] = $this->singleSlideSection($i);
        }

        return $sections;
    }

    protected function singleSlideSection(int $i): Section
    {
        $isOptional = $i > 3;

        return Section::make("Slide {$i}" . ($isOptional ? ' (Optional extra slide)' : ''))
            ->description("Customize banner image, headline, subtitle, and buttons for Slide {$i}.")
            ->icon('heroicon-o-photo')
            ->collapsible()
            ->collapsed($isOptional && blank($this->data["slide_{$i}_image"] ?? null))
            ->schema([
                Grid::make(['default' => 1, 'xl' => 12])
                    ->schema([
                        // Left column: Current image preview + fresh upload box
                        Group::make([
                            Hidden::make("slide_{$i}_image"),

                            Placeholder::make("slide_{$i}_preview")
                                ->label("Current Slide {$i} Image")
                                ->content(function ($get) use ($i): HtmlString {
                                    $uploaded = $get("slide_{$i}_upload");
                                    if (is_array($uploaded)) {
                                        $uploaded = reset($uploaded) ?: null;
                                    }
                                    $path = is_string($uploaded) && filled($uploaded)
                                        ? $uploaded
                                        : $get("slide_{$i}_image");

                                    $url = Media::url(is_string($path) ? $path : null);
                                    $title = e((string) ($get("slide_{$i}_title") ?: "Slide {$i}"));
                                    $eyebrow = e((string) ($get("slide_{$i}_eyebrow") ?: ''));

                                    if (!$url) {
                                        return new HtmlString(
                                            '<div style="height:180px;border-radius:12px;border:2px dashed #cbd5e1;display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:600;">No image uploaded for Slide ' . $i . ' yet</div>'
                                        );
                                    }

                                    return new HtmlString(
                                        '<div style="position:relative;height:190px;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;background:#0f172a;">'
                                        . '<img src="' . e($url) . '" alt="' . $title . '" style="width:100%;height:100%;object-fit:cover;display:block;" />'
                                        . '<div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,0,0,0.72) 0%,rgba(0,0,0,0.2) 100%);display:flex;flex-direction:column;justify-content:flex-end;padding:14px;color:#fff;">'
                                        . ($eyebrow !== '' ? '<span style="font-size:11px;text-transform:uppercase;letter-spacing:0.12em;color:#fb923c;font-weight:700;">' . $eyebrow . '</span>' : '')
                                        . '<strong style="font-size:16px;line-height:1.25;margin-top:2px;">' . $title . '</strong>'
                                        . '</div>'
                                        . '</div>'
                                    );
                                }),

                            FileUpload::make("slide_{$i}_upload")
                                ->label("Upload / Change Slide {$i} Image")
                                ->image()
                                ->disk('public')
                                ->directory('homepage/slides')
                                ->imagePreviewHeight('140')
                                ->maxSize(8192)
                                ->live()
                                ->afterStateUpdated(function ($state, $set) use ($i): void {
                                    if (is_string($state) && filled($state)) {
                                        $set("slide_{$i}_image", $state);
                                        $set("slide_{$i}_is_active", true);
                                    }
                                })
                                ->helperText('Click "Browse" or drop a new image here, then click "Save changes" at the top.'),
                        ])->columnSpan(['xl' => 5]),

                        // Right column: Slide text, buttons, alignment & colours
                        Group::make([
                            Grid::make(6)->schema([
                                Toggle::make("slide_{$i}_is_active")
                                    ->label("Show Slide {$i} on homepage")
                                    ->default($i <= 3)
                                    ->columnSpan(3),

                                Select::make("slide_{$i}_text_align")
                                    ->label('Text alignment')
                                    ->native(false)
                                    ->options([
                                        'left' => 'Left aligned',
                                        'center' => 'Center aligned',
                                        'right' => 'Right aligned',
                                    ])
                                    ->default('left')
                                    ->columnSpan(2),

                                TextInput::make("slide_{$i}_overlay_opacity")
                                    ->label('Overlay %')
                                    ->suffix('%')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(95)
                                    ->default(75)
                                    ->columnSpan(1),

                                TextInput::make("slide_{$i}_eyebrow")
                                    ->label('Small badge / top text')
                                    ->placeholder('e.g. MEMBER OFFER')
                                    ->prefixIcon('heroicon-o-sparkles')
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->columnSpan(2),

                                TextInput::make("slide_{$i}_title")
                                    ->label('Main headline')
                                    ->placeholder('e.g. Big savings on customer favourites')
                                    ->prefixIcon('heroicon-o-megaphone')
                                    ->maxLength(180)
                                    ->live(onBlur: true)
                                    ->columnSpan(4),

                                Textarea::make("slide_{$i}_description")
                                    ->label('Subtitle / description')
                                    ->placeholder('e.g. Limited-time prices across popular categories.')
                                    ->rows(2)
                                    ->maxLength(350)
                                    ->columnSpanFull(),

                                TextInput::make("slide_{$i}_button_label")
                                    ->label('Primary button text')
                                    ->placeholder('Explore Deals')
                                    ->prefixIcon('heroicon-o-cursor-arrow-rays')
                                    ->columnSpan(3),

                                TextInput::make("slide_{$i}_button_url")
                                    ->label('Primary button URL')
                                    ->placeholder('/products?sort=best-selling')
                                    ->prefixIcon('heroicon-o-link')
                                    ->columnSpan(3),

                                TextInput::make("slide_{$i}_secondary_button_label")
                                    ->label('Second button text (optional)')
                                    ->placeholder('Leave blank for 1 button')
                                    ->columnSpan(3),

                                TextInput::make("slide_{$i}_secondary_button_url")
                                    ->label('Second button URL (optional)')
                                    ->placeholder('/products')
                                    ->prefixIcon('heroicon-o-link')
                                    ->columnSpan(3),

                                ColorPicker::make("slide_{$i}_eyebrow_color")
                                    ->label('Badge colour')
                                    ->columnSpan(2),

                                ColorPicker::make("slide_{$i}_title_color")
                                    ->label('Headline colour')
                                    ->columnSpan(2),

                                ColorPicker::make("slide_{$i}_button_color")
                                    ->label('Button colour')
                                    ->columnSpan(2),
                            ]),
                        ])->columnSpan(['xl' => 7]),
                    ]),
            ]);
    }

    protected function heroSliderTab(): Tab
    {
        return Tab::make('Hero slider')
            ->icon('heroicon-o-photo')
            ->schema($this->heroSliderSections());
    }

    protected function brandingTab(): Tab
    {
        return Tab::make('Store Identity & SEO')->icon('heroicon-o-building-storefront')->schema([
            Section::make('Store identity & SEO meta')
                ->description('Store name, tagline, currency, order prefix, and default search engine meta tags.')
                ->icon('heroicon-o-building-storefront')
                ->columns(6)
                ->schema([
                    TextInput::make('site_name')
                        ->label('Store name')
                        ->prefixIcon('heroicon-o-building-storefront')
                        ->required()
                        ->columnSpan(3),

                    TextInput::make('site_tagline')
                        ->label('Store tagline / slogan')
                        ->prefixIcon('heroicon-o-chat-bubble-bottom-center-text')
                        ->placeholder('Gadgets & Accessories Store')
                        ->columnSpan(3),

                    TextInput::make('site_currency')
                        ->label('Currency code')
                        ->placeholder('BDT')
                        ->maxLength(10)
                        ->columnSpan(2),

                    TextInput::make('site_currency_symbol')
                        ->label('Currency symbol')
                        ->placeholder('৳')
                        ->maxLength(5)
                        ->columnSpan(2),

                    TextInput::make('order_prefix')
                        ->label('Order number prefix')
                        ->placeholder('CB')
                        ->maxLength(10)
                        ->helperText('Example: CB-261111-XXXX')
                        ->columnSpan(2),

                    TextInput::make('site_meta_title')
                        ->label('Default SEO Meta Title')
                        ->prefixIcon('heroicon-o-magnifying-glass')
                        ->placeholder('Classybazar — Best Gadgets & Online Shopping in Bangladesh')
                        ->maxLength(180)
                        ->columnSpan(3),

                    TextInput::make('site_meta_keywords')
                        ->label('SEO Meta Keywords')
                        ->prefixIcon('heroicon-o-tag')
                        ->placeholder('classybazar, gadgets, electronics, fashion')
                        ->maxLength(250)
                        ->columnSpan(3),

                    Textarea::make('site_meta_description')
                        ->label('Default SEO Meta Description')
                        ->rows(3)
                        ->maxLength(320)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    protected function logoFaviconTab(): Tab
    {
        return Tab::make('Logo & Favicon')->icon('heroicon-o-sparkles')->schema([
            Section::make('Store logos & browser favicon')
                ->description('Preview and upload your Header Logo, Footer Logo, and Browser Tab Favicon separately.')
                ->icon('heroicon-o-photo')
                ->columns(3)
                ->schema([
                    // 1. Header Logo
                    Group::make([
                        Hidden::make('site_logo'),
                        Placeholder::make('site_logo_preview')
                            ->label('Current Header Logo')
                            ->content(function ($get): HtmlString {
                                $path = $get('site_logo');
                                $url = filled($path) ? Media::url($path) : null;
                                if (!$url) {
                                    return new HtmlString('<div style="height:100px;border:2px dashed #cbd5e1;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:12px;">No header logo set</div>');
                                }

                                return new HtmlString(
                                    '<div style="height:100px;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;display:flex;align-items:center;justify-content:center;padding:12px;">'
                                    . '<img src="' . e($url) . '" alt="Header Logo" style="max-height:76px;max-width:100%;object-fit:contain;" />'
                                    . '</div>'
                                );
                            }),
                        FileUpload::make('site_logo_upload')
                            ->label('Upload / Change Header Logo')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                $file = is_array($state) ? (reset($state) ?: null) : $state;
                                if (filled($file) && is_string($file)) {
                                    $set('site_logo', $file);
                                }
                            })
                            ->helperText('Recommended: transparent PNG or SVG (around 200×70px).'),
                    ]),

                    // 2. Footer Logo
                    Group::make([
                        Hidden::make('site_footer_logo'),
                        Placeholder::make('site_footer_logo_preview')
                            ->label('Current Footer Logo')
                            ->content(function ($get): HtmlString {
                                $path = $get('site_footer_logo') ?: $get('site_logo');
                                $url = filled($path) ? Media::url($path) : null;
                                if (!$url) {
                                    return new HtmlString('<div style="height:100px;border:2px dashed #cbd5e1;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:12px;">No footer logo set</div>');
                                }

                                return new HtmlString(
                                    '<div style="height:100px;background:#0f172a;border:1px solid #1e293b;border-radius:12px;display:flex;align-items:center;justify-content:center;padding:12px;">'
                                    . '<img src="' . e($url) . '" alt="Footer Logo" style="max-height:76px;max-width:100%;object-fit:contain;" />'
                                    . '</div>'
                                );
                            }),
                        FileUpload::make('site_footer_logo_upload')
                            ->label('Upload / Change Footer Logo')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                $file = is_array($state) ? (reset($state) ?: null) : $state;
                                if (filled($file) && is_string($file)) {
                                    $set('site_footer_logo', $file);
                                }
                            })
                            ->helperText('Displayed on dark footer background.'),
                    ]),

                    // 3. Browser Favicon
                    Group::make([
                        Hidden::make('site_favicon'),
                        Placeholder::make('site_favicon_preview')
                            ->label('Current Browser Favicon')
                            ->content(function ($get): HtmlString {
                                $path = $get('site_favicon');
                                $name = e((string) ($get('site_name') ?: 'Classybazar'));
                                $url = filled($path) ? Media::url($path) : null;
                                if (!$url) {
                                    return new HtmlString('<div style="height:100px;border:2px dashed #cbd5e1;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:12px;">No favicon set</div>');
                                }

                                return new HtmlString(
                                    '<div style="height:100px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;display:flex;align-items:center;justify-content:center;padding:12px;">'
                                    . '<div style="display:inline-flex;align-items:center;gap:10px;background:#ffffff;border:1px solid #cbd5e1;border-radius:10px;padding:10px 16px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">'
                                    . '<img src="' . e($url) . '" alt="Favicon" style="width:28px;height:28px;object-fit:contain;border-radius:4px;" />'
                                    . '<span style="font-size:13px;font-weight:600;color:#0f172a;">' . $name . '</span>'
                                    . '</div>'
                                    . '</div>'
                                );
                            }),
                        FileUpload::make('site_favicon_upload')
                            ->label('Upload / Change Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->live()
                            ->afterStateUpdated(function ($state, $set): void {
                                $file = is_array($state) ? (reset($state) ?: null) : $state;
                                if (filled($file) && is_string($file)) {
                                    $set('site_favicon', $file);
                                }
                            })
                            ->helperText('Square PNG, SVG, or ICO (64×64px recommended).'),
                    ]),
                ]),
        ]);
    }

    protected function themeSettingsTab(): Tab
    {
        return Tab::make('Theme & Colours')->icon('heroicon-o-paint-brush')->schema([
            Section::make('Brand colour palette & live theme preview')
                ->description('Pick a one-click professional colour preset or customize every element of your storefront.')
                ->icon('heroicon-o-paint-brush')
                ->columns(4)
                ->schema([
                    Select::make('theme_preset')
                        ->label('Quick colour theme preset')
                        ->native(false)
                        ->placeholder('Select a preset to auto-fill colours...')
                        ->options([
                            'orange' => 'Classy Orange (Default — #EB6E1B)',
                            'indigo' => 'Royal Indigo (#4F46E5)',
                            'emerald' => 'Emerald Luxe (#059669)',
                            'crimson' => 'Crimson Red (#DB4444)',
                            'ocean' => 'Ocean Blue (#0284C7)',
                            'gold' => 'Midnight Gold (#D97706)',
                        ])
                        ->live()
                        ->afterStateUpdated(function (?string $state, $set): void {
                            $presets = [
                                'orange' => ['primary' => '#eb6e1b', 'secondary' => '#111827', 'button' => '#eb6e1b', 'hover' => '#111827', 'badge' => '#ef4444', 'topbar' => '#111111', 'footer' => '#050505'],
                                'indigo' => ['primary' => '#4f46e5', 'secondary' => '#1e1b4b', 'button' => '#4f46e5', 'hover' => '#312e81', 'badge' => '#ec4899', 'topbar' => '#1e1b4b', 'footer' => '#0f172a'],
                                'emerald' => ['primary' => '#059669', 'secondary' => '#064e3b', 'button' => '#059669', 'hover' => '#065f46', 'badge' => '#f59e0b', 'topbar' => '#064e3b', 'footer' => '#022c22'],
                                'crimson' => ['primary' => '#db4444', 'secondary' => '#111827', 'button' => '#db4444', 'hover' => '#991b1b', 'badge' => '#dc2626', 'topbar' => '#111827', 'footer' => '#09090b'],
                                'ocean' => ['primary' => '#0284c7', 'secondary' => '#0c4a6e', 'button' => '#0284c7', 'hover' => '#075985', 'badge' => '#f97316', 'topbar' => '#0c4a6e', 'footer' => '#082f49'],
                                'gold' => ['primary' => '#d97706', 'secondary' => '#1c1917', 'button' => '#d97706', 'hover' => '#92400e', 'badge' => '#ef4444', 'topbar' => '#1c1917', 'footer' => '#0c0a09'],
                            ];
                            if (!isset($presets[$state])) {
                                return;
                            }
                            $p = $presets[$state];
                            $set('theme_primary_color', $p['primary']);
                            $set('theme_secondary_color', $p['secondary']);
                            $set('theme_button_color', $p['button']);
                            $set('theme_button_hover_color', $p['hover']);
                            $set('theme_badge_color', $p['badge']);
                            $set('theme_topbar_background', $p['topbar']);
                            $set('theme_footer_background', $p['footer']);
                        })
                        ->columnSpan(2),

                    Placeholder::make('live_theme_preview')
                        ->label('Live Storefront Colour Preview')
                        ->content(function ($get): HtmlString {
                            $primary = e((string) ($get('theme_primary_color') ?: '#eb6e1b'));
                            $btnBg = e((string) ($get('theme_button_color') ?: '#eb6e1b'));
                            $btnText = e((string) ($get('theme_button_text_color') ?: '#ffffff'));
                            $topbarBg = e((string) ($get('theme_topbar_background') ?: '#111111'));
                            $topbarText = e((string) ($get('theme_topbar_text_color') ?: '#fafafa'));
                            $headerBg = e((string) ($get('theme_header_background') ?: '#ffffff'));
                            $headerText = e((string) ($get('theme_header_text_color') ?: '#111827'));
                            $badge = e((string) ($get('theme_badge_color') ?: '#ef4444'));
                            $footerBg = e((string) ($get('theme_footer_background') ?: '#050505'));
                            $footerText = e((string) ($get('theme_footer_text_color') ?: '#ffffff'));

                            return new HtmlString(
                                '<div style="border:1px solid #cbd5e1;border-radius:12px;overflow:hidden;font-size:11px;">'
                                . '<div style="background:' . $topbarBg . ';color:' . $topbarText . ';padding:5px 12px;text-align:center;font-weight:500;">Topbar Preview — Free Express Delivery!</div>'
                                . '<div style="background:' . $headerBg . ';color:' . $headerText . ';padding:10px 14px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #e2e8f0;">'
                                . '<span style="font-weight:800;color:' . $primary . ';">CLASSY BAZAR</span>'
                                . '<div style="display:flex;align-items:center;gap:8px;">'
                                . '<span style="background:' . $badge . ';color:#fff;padding:3px 8px;border-radius:999px;font-weight:700;font-size:10px;">-30% SALE</span>'
                                . '<span style="background:' . $btnBg . ';color:' . $btnText . ';padding:5px 12px;border-radius:6px;font-weight:600;">Shop Now</span>'
                                . '</div>'
                                . '</div>'
                                . '<div style="background:' . $footerBg . ';color:' . $footerText . ';padding:6px 12px;text-align:center;opacity:0.95;">Footer Preview</div>'
                                . '</div>'
                            );
                        })
                        ->columnSpan(2),

                    ColorPicker::make('theme_primary_color')->label('Primary brand accent')->live(),
                    ColorPicker::make('theme_secondary_color')->label('Secondary / dark accent')->live(),
                    ColorPicker::make('theme_button_color')->label('Button background')->live(),
                    ColorPicker::make('theme_button_hover_color')->label('Button hover colour')->live(),

                    ColorPicker::make('theme_button_text_color')->label('Button text colour')->live(),
                    ColorPicker::make('theme_badge_color')->label('Sale / discount badge')->live(),
                    ColorPicker::make('theme_topbar_background')->label('Topbar background')->live(),
                    ColorPicker::make('theme_topbar_text_color')->label('Topbar text colour')->live(),

                    ColorPicker::make('theme_header_background')->label('Header background')->live(),
                    ColorPicker::make('theme_header_text_color')->label('Header text colour')->live(),
                    ColorPicker::make('theme_menubar_background')->label('Menu bar background')->live(),
                    ColorPicker::make('theme_menubar_text_color')->label('Menu bar text colour')->live(),

                    ColorPicker::make('theme_body_background')->label('Page body background')->live(),
                    ColorPicker::make('theme_card_background')->label('Product card background')->live(),
                    ColorPicker::make('theme_text_color')->label('Main body text colour')->live(),
                    ColorPicker::make('theme_footer_background')->label('Footer background')->live(),

                    ColorPicker::make('theme_footer_text_color')->label('Footer text colour')->live(),
                    Select::make('theme_font_heading')->label('Heading Google Font')->options($this->fonts())->searchable()->native(false),
                    Select::make('theme_font_body')->label('Body Google Font')->options($this->fonts())->searchable()->native(false),
                ]),
        ]);
    }

    protected function homePageTab(): Tab
    {
        return Tab::make('Home page')->icon('heroicon-o-home')->schema([
            Section::make('Category showcase')
                ->description('Choose which categories are highlighted on the homepage.')
                ->icon('heroicon-o-squares-2x2')
                ->schema([
                    Select::make('home_category_ids')->label('Categories shown on homepage')
                        ->multiple()->searchable()->preload()
                        ->options(fn() => Category::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all())
                        ->helperText('Leave blank to show all active categories.'),
                ]),
            Section::make('Homepage sections')
                ->description('Drag to reorder. Disable any section you do not want to show.')
                ->icon('heroicon-o-rectangle-group')
                ->schema([
                    Repeater::make('home_sections')->hiddenLabel()->reorderable()->collapsible()
                        ->addable(false)->deletable(false)
                        ->itemLabel(fn(array $state) => $this->homeSectionLabel($state['type'] ?? ''))
                        ->columns(4)
                        ->schema([
                            Hidden::make('type'),
                            Toggle::make('enabled')->label('Show')->default(true),
                            TextInput::make('eyebrow')->label('Small heading')->maxLength(100),
                            TextInput::make('title')->label('Main heading')->maxLength(180)->columnSpan(2),
                            TextInput::make('limit')->label('Item limit')->numeric()->minValue(1)->maxValue(30),
                        ]),
                ]),
            Section::make('Service highlights')
                ->description('Trust badges and delivery guarantees shown near the bottom of the homepage.')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Repeater::make('home_support_items')->hiddenLabel()->reorderable()->minItems(1)->maxItems(4)
                        ->itemLabel(fn(array $state) => $state['title'] ?? 'Service highlight')->columns(2)
                        ->schema([
                            TextInput::make('title')->required()->maxLength(120),
                            TextInput::make('description')->required()->maxLength(220),
                        ]),
                ]),
        ]);
    }

    protected function headerTab(): Tab
    {
        return Tab::make('Header')->icon('heroicon-o-bars-3')->schema([
            Section::make('Header behaviour')
                ->description('Configure sticky navigation and the top search bar.')
                ->icon('heroicon-o-bars-3')
                ->columns(2)
                ->schema([
                    Toggle::make('header_sticky')->label('Sticky header on scroll')->default(true),
                    Toggle::make('header_show_search')->label('Show product search bar')->default(true),
                ]),
            Section::make('Top announcement bar')
                ->description('Display a promotional banner above the main header.')
                ->icon('heroicon-o-megaphone')
                ->columns(2)
                ->schema([
                    Toggle::make('header_show_topbar')->label('Show announcement bar')->live()->columnSpanFull(),
                    TextInput::make('header_topbar_text')->label('Announcement message')
                        ->placeholder('e.g. Summer Sale — Up to 50% Off + Free Express Delivery!')
                        ->columnSpanFull()
                        ->visible(fn($get) => $get('header_show_topbar')),
                    TextInput::make('header_topbar_link_label')->label('Action link text')
                        ->placeholder('Shop Now')
                        ->visible(fn($get) => $get('header_show_topbar')),
                    TextInput::make('header_topbar_link_url')->label('Action link URL')
                        ->placeholder('/products')
                        ->visible(fn($get) => $get('header_show_topbar')),
                ]),
        ]);
    }

    protected function footerTab(): Tab
    {
        return Tab::make('Footer')->icon('heroicon-o-bars-3-bottom-left')->schema([
            Section::make('Footer content & newsletter')
                ->description('Manage footer bio, copyright notice, and newsletter callout.')
                ->icon('heroicon-o-bars-3-bottom-left')
                ->columns(2)
                ->schema([
                    Textarea::make('footer_about')->label('About text')->rows(3)->columnSpanFull(),
                    TextInput::make('footer_subscribe_title')->label('Newsletter heading')->prefixIcon('heroicon-o-envelope'),
                    TextInput::make('footer_subscribe_text')->label('Newsletter description'),
                    TextInput::make('footer_copyright')->label('Copyright notice')->columnSpanFull(),
                ]),
            Section::make('Social media profiles')
                ->description('Links to your official social pages shown in the footer.')
                ->icon('heroicon-o-share')
                ->schema([
                    Repeater::make('footer_social')->hiddenLabel()->columns(2)
                        ->reorderable()->defaultItems(0)->addActionLabel('Add social link')
                        ->schema([
                            Select::make('platform')->native(false)->options([
                                'facebook' => 'Facebook',
                                'instagram' => 'Instagram',
                                'youtube' => 'YouTube',
                                'tiktok' => 'TikTok',
                                'linkedin' => 'LinkedIn',
                                'whatsapp' => 'WhatsApp',
                            ])->required(),
                            TextInput::make('url')->prefixIcon('heroicon-o-link')->url()->required(),
                        ]),
                ]),
        ]);
    }

    protected function contactTab(): Tab
    {
        return Tab::make('Contact')->icon('heroicon-o-phone')->schema([
            Section::make('Store contact details')
                ->description('Customer support phone, email, working hours, and physical address displayed on the Contact page and footer.')
                ->icon('heroicon-o-phone')
                ->columns(3)
                ->schema([
                    TextInput::make('contact_phone')->label('Support phone')->prefixIcon('heroicon-o-phone')->placeholder('+880 1XXX-XXXXXX'),
                    TextInput::make('contact_email')->label('Support email')->prefixIcon('heroicon-o-envelope')->email()->placeholder('support@classybazar.com'),
                    TextInput::make('contact_working_hours')->label('Working hours')->prefixIcon('heroicon-o-clock')->placeholder('Sat - Thu: 9:00 AM - 10:00 PM'),
                    Textarea::make('contact_address')->label('Physical address')->rows(3)->columnSpanFull(),
                ]),

            Section::make('Floating live chat button (WhatsApp & Messenger)')
                ->description('Configure the floating quick-contact button displayed at the bottom-right corner of the storefront.')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->columns(2)
                ->schema([
                    Toggle::make('contact_show_floating_chat')
                        ->label('Show floating chat button on storefront')
                        ->default(true)
                        ->columnSpanFull(),

                    TextInput::make('contact_whatsapp')
                        ->label('WhatsApp number (with country code)')
                        ->prefixIcon('heroicon-o-phone')
                        ->placeholder('8801793293600')
                        ->helperText('Digits only with country code, e.g. 88017XXXXXXXX'),

                    TextInput::make('contact_messenger')
                        ->label('Facebook Messenger username / Page ID')
                        ->prefixIcon('heroicon-o-chat-bubble-oval-left-ellipsis')
                        ->placeholder('amarClassyBazar')
                        ->helperText('Used for https://m.me/your-page-username'),
                ]),
        ]);
    }

    protected function shippingTab(): Tab
    {
        return Tab::make('Shipping')->icon('heroicon-o-truck')->schema([
            Section::make('Delivery charges')
                ->description('Set standard delivery rates in Taka and optional free shipping threshold.')
                ->icon('heroicon-o-truck')
                ->columns(3)
                ->schema([
                    TextInput::make('shipping_inside_dhaka')->label('Inside Dhaka')->prefix('৳')->numeric()->required(),
                    TextInput::make('shipping_outside_dhaka')->label('Outside Dhaka')->prefix('৳')->numeric()->required(),
                    TextInput::make('shipping_free_above')->label('Free delivery above')->prefix('৳')->numeric()
                        ->helperText('Set 0 to never give free delivery.'),
                ]),
        ]);
    }

    protected function trackingTab(): Tab
    {
        return Tab::make('Tracking')->icon('heroicon-o-chart-bar')->schema([
            Section::make('Marketing & analytics pixels')
                ->description('Enter your pixel/measurement IDs. Blank fields will not load any script.')
                ->icon('heroicon-o-chart-bar')
                ->columns(2)
                ->schema([
                    TextInput::make('tracking_fb_pixel_id')->label('Facebook Pixel ID')->prefixIcon('heroicon-o-code-bracket')->placeholder('e.g. 123456789012345'),
                    TextInput::make('tracking_ga_measurement_id')->label('Google Analytics 4 ID')->prefixIcon('heroicon-o-chart-pie')->placeholder('G-XXXXXXX'),
                    TextInput::make('tracking_gtm_id')->label('Google Tag Manager ID')->prefixIcon('heroicon-o-tag')->placeholder('GTM-XXXXXX'),
                    TextInput::make('tracking_tiktok_pixel_id')->label('TikTok Pixel ID')->prefixIcon('heroicon-o-video-camera')->placeholder('e.g. CXXXXXXXXXXXXXXX'),
                ]),
        ]);
    }

    protected function couriersTab(): Tab
    {
        return Tab::make('Couriers')->icon('heroicon-o-paper-airplane')->schema([
            Section::make('Steadfast Courier')
                ->description('API credentials from your Steadfast merchant portal for one-click parcel booking.')
                ->icon('heroicon-o-truck')
                ->columns(2)
                ->schema([
                    TextInput::make('courier_steadfast_api_key')->label('API Key')->password()->revealable(),
                    TextInput::make('courier_steadfast_secret_key')->label('Secret Key')->password()->revealable(),
                    TextInput::make('courier_steadfast_base_url')->label('Base URL')->prefixIcon('heroicon-o-globe-alt')->columnSpanFull(),
                ]),
            Section::make('Pathao Courier')
                ->description('Hermes API credentials and default pickup/delivery parameters for Pathao.')
                ->icon('heroicon-o-paper-airplane')
                ->columns(2)
                ->schema([
                    TextInput::make('courier_pathao_base_url')->label('Base URL')->prefixIcon('heroicon-o-globe-alt')->columnSpanFull(),
                    TextInput::make('courier_pathao_client_id')->label('Client ID'),
                    TextInput::make('courier_pathao_client_secret')->label('Client secret')->password()->revealable(),
                    TextInput::make('courier_pathao_username')->label('Username / Email'),
                    TextInput::make('courier_pathao_password')->label('Password')->password()->revealable(),
                    TextInput::make('courier_pathao_store_id')->label('Store ID'),
                    TextInput::make('courier_pathao_city_id')->label('Default city ID'),
                    TextInput::make('courier_pathao_zone_id')->label('Default zone ID'),
                    Select::make('courier_pathao_delivery_type')->label('Delivery type')->native(false)
                        ->options([48 => 'Normal (48h)', 12 => 'On demand (12h)'])->default(48),
                    TextInput::make('courier_pathao_default_weight')->label('Default weight (kg)')->suffix('kg')->numeric(),
                ]),
        ]);
    }
}
