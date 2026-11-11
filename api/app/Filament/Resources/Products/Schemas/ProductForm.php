<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Support\Money;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])
                    ->gap(true)
                    ->schema([
                        // ---------------- Main column ----------------
                        Group::make([
                            Section::make('Product details')
                                ->description('Name, category and description shown on the product page.')
                                ->icon('heroicon-o-cube')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('title')
                                        ->label('Product name')
                                        ->placeholder('e.g. Premium Cotton T-Shirt')
                                        ->prefixIcon('heroicon-o-tag')
                                        ->required()
                                        ->maxLength(200)
                                        ->columnSpanFull(),

                                    Select::make('product_type')
                                        ->label('Product type')
                                        ->options([
                                            'simple' => 'Simple product',
                                            'variable' => 'Variable product',
                                        ])
                                        ->default('simple')
                                        ->required()
                                        ->live()
                                        ->native(false)
                                        ->prefixIcon('heroicon-o-squares-2x2')
                                        ->helperText('Variable products can have separate options, prices, stock and images.'),

                                    Select::make('category_id')
                                        ->label('Category')
                                        ->relationship('category', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->prefixIcon('heroicon-o-folder')
                                        ->placeholder('Choose a category')
                                        ->required(),

                                    TextInput::make('brand')
                                        ->placeholder('e.g. Nike')
                                        ->prefixIcon('heroicon-o-building-storefront')
                                        ->maxLength(120),

                                    TextInput::make('sku')
                                        ->label('SKU')
                                        ->placeholder('e.g. TSH-001')
                                        ->prefixIcon('heroicon-o-qr-code')
                                        ->maxLength(80)
                                        ->unique(ignoreRecord: true)
                                        ->visible(fn ($get) => $get('product_type') !== 'variable'),

                                    TextInput::make('slug')
                                        ->placeholder('auto-generated-from-name')
                                        ->prefixIcon('heroicon-o-link')
                                        ->helperText('Leave blank to use the product name. Duplicate names receive -2, -3, etc.')
                                        ->maxLength(220)
                                        ->columnSpanFull(),

                                    Textarea::make('description')
                                        ->placeholder('Describe the product: material, size, features...')
                                        ->rows(5)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Pricing & stock')
                                ->description('Prices are entered in taka. Discount is applied automatically.')
                                ->icon('heroicon-o-banknotes')
                                ->columns(3)
                                ->visible(fn ($get) => $get('product_type') !== 'variable')
                                ->schema([
                                    // Prices are stored as integer poisha to keep the maths
                                    // exact. The admin types taka, so convert on the way in
                                    // and back out again when the form loads.
                                    TextInput::make('price')
                                        ->label('Price')
                                        ->prefix('à§³')
                                        ->placeholder('0.00')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->required(fn ($get) => $get('product_type') !== 'variable')
                                        ->formatStateUsing(fn ($state) => $state === null ? null : Money::toTaka((int) $state))
                                        ->dehydrateStateUsing(fn ($state) => blank($state) ? null : Money::toPoisha($state)),

                                    TextInput::make('discount_percentage')
                                        ->label('Discount')
                                        ->suffix('%')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(99)
                                        ->default(0),

                                    TextInput::make('stock')
                                        ->label('Stock quantity')
                                        ->suffixIcon('heroicon-o-archive-box')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->required(fn ($get) => $get('product_type') !== 'variable'),
                                ]),

                            Section::make('Variation options')
                                ->description('Define option names and all values once. Example: Color = Red, Blue and Size = M, L.')
                                ->icon('heroicon-o-adjustments-horizontal')
                                ->visible(fn ($get) => $get('product_type') === 'variable')
                                ->schema([
                                    Repeater::make('variation_options')
                                        ->hiddenLabel()
                                        ->columns(2)
                                        ->minItems(1)
                                        ->defaultItems(1)
                                        ->required()
                                        ->reorderable()
                                        ->addActionLabel('Add another option')
                                        ->itemLabel(fn (array $state) => $state['name'] ?? 'New option')
                                        ->live()
                                        ->afterStateUpdated(function ($state, $get, $set): void {
                                            $set('variants', self::syncVariantOptionRows(
                                                (array) ($get('variants') ?? []),
                                                (array) ($state ?? []),
                                            ));
                                        })
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Option name')
                                                ->placeholder('Color')
                                                ->required()
                                                ->live(onBlur: true),

                                            TagsInput::make('values')
                                                ->label('Available values')
                                                ->placeholder('Type a value and press Enter')
                                                ->required()
                                                ->live(),
                                        ]),
                                ]),

                            Section::make('Variations')
                                ->description('Add a sellable combination, choose its option values, then enter price and stock.')
                                ->icon('heroicon-o-rectangle-stack')
                                ->visible(fn ($get) => $get('product_type') === 'variable')
                                ->schema([
                                    Repeater::make('variants')
                                        ->relationship()
                                        ->orderColumn('position')
                                        ->reorderable()
                                        ->collapsible()
                                        ->cloneable()
                                        ->minItems(1)
                                        ->required()
                                        ->addActionLabel('Add variation')
                                        ->itemLabel(function (array $state) {
                                            if (filled($state['name'] ?? null)) {
                                                return $state['name'];
                                            }

                                            return implode(' / ', array_values(self::rowsToAttributes($state['attributes'] ?? []))) ?: 'New variation';
                                        })
                                        ->columns(6)
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Name (optional)')
                                                ->placeholder('Auto-generated from selected options')
                                                ->maxLength(160)
                                                ->columnSpan(3),

                                            TextInput::make('sku')
                                                ->label('Variation SKU')
                                                ->maxLength(80)
                                                ->unique(ignoreRecord: true)
                                                ->columnSpan(3),

                                            Repeater::make('attributes')
                                                ->label('Options')
                                                ->default(fn ($get) => self::attributeRows([], (array) $get('../../variation_options')))
                                                ->formatStateUsing(fn ($state, $get) => self::attributeRows(
                                                    (array) ($state ?? []),
                                                    (array) $get('../../variation_options'),
                                                ))
                                                ->dehydrateStateUsing(fn ($state, $get) => self::rowsToAttributes(
                                                    (array) ($state ?? []),
                                                    (array) $get('../../variation_options'),
                                                ))
                                                ->addable(false)
                                                ->deletable(false)
                                                ->reorderable(false)
                                                ->columns(2)
                                                ->itemLabel(fn (array $state) => $state['name'] ?? 'Option')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Option')
                                                        ->readOnly(),

                                                    Select::make('value')
                                                        ->label('Choose value')
                                                        ->options(fn ($get) => self::valuesForOption(
                                                            (string) $get('name'),
                                                            (array) $get('../../../../variation_options'),
                                                        ))
                                                        ->native(false)
                                                        ->required(),
                                                ])
                                                ->required()
                                                ->columnSpanFull(),

                                            TextInput::make('price')
                                                ->label('Price')
                                                ->prefix('à§³')
                                                ->numeric()
                                                ->minValue(0)
                                                ->required()
                                                ->formatStateUsing(fn ($state) => $state === null ? null : Money::toTaka((int) $state))
                                                ->dehydrateStateUsing(fn ($state) => blank($state) ? null : Money::toPoisha($state))
                                                ->columnSpan(2),

                                            TextInput::make('wholesale_price')
                                                ->label('Wholesale price')
                                                ->prefix('à§³')
                                                ->numeric()
                                                ->minValue(0.01)
                                                ->required(fn ($get) => (bool) $get('../../is_wholesale'))
                                                ->visible(fn ($get) => (bool) $get('../../is_wholesale'))
                                                ->formatStateUsing(fn ($state) => $state === null ? null : Money::toTaka((int) $state))
                                                ->dehydrateStateUsing(fn ($state) => blank($state) ? null : Money::toPoisha($state))
                                                ->columnSpan(2),

                                            TextInput::make('discount_percentage')
                                                ->label('Discount')
                                                ->suffix('%')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(99)
                                                ->default(0)
                                                ->columnSpan(1),

                                            TextInput::make('stock')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0)
                                                ->required()
                                                ->columnSpan(1),

                                            FileUpload::make('image')
                                                ->label('Variation image')
                                                ->image()
                                                ->imagePreviewHeight('120')
                                                ->disk('public')
                                                ->directory('products/variants')
                                                ->maxSize(4096)
                                                ->columnSpan(4),

                                            Toggle::make('is_active')
                                                ->label('Available for sale')
                                                ->default(true)
                                                ->columnSpan(2),
                                        ]),
                                ]),

                            Section::make('Images')
                                ->description('Main image is the product thumbnail. Gallery images appear on the product page.')
                                ->icon('heroicon-o-photo')
                                ->columns(2)
                                ->schema([
                                    FileUpload::make('thumbnail')
                                        ->label('Main image')
                                        ->image()
                                        ->imagePreviewHeight('180')
                                        ->disk('public')
                                        ->directory('products')
                                        ->maxSize(4096)
                                        ->helperText('JPG, PNG or WebP up to 4 MB.'),

                                    FileUpload::make('images')
                                        ->label('Gallery')
                                        ->image()
                                        ->multiple()
                                        ->reorderable()
                                        ->disk('public')
                                        ->directory('products')
                                        ->maxFiles(12)
                                        ->maxSize(4096)
                                        ->helperText('Up to 12 images. Drag to reorder; the first shows first.'),
                                ]),
                        ])->columnSpan(['xl' => 2]),

                        // ---------------- Side column ----------------
                        Group::make([
                            Section::make('Visibility')
                                ->description('Control where this product appears.')
                                ->icon('heroicon-o-eye')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label('Published')
                                        ->default(true)
                                        ->helperText('Unpublished products disappear from the storefront but stay on past orders.'),

                                    Toggle::make('is_featured')
                                        ->label('Featured')
                                        ->helperText('Shows in the "Our Products" section.'),

                                    Toggle::make('is_flash_sale')
                                        ->label('Flash sale')
                                        ->live(),

                                    DateTimePicker::make('flash_sale_ends_at')
                                        ->label('Flash sale ends')
                                        ->native(false)
                                        ->visible(fn ($get) => (bool) $get('is_flash_sale'))
                                        ->helperText('Leave blank to run until you turn it off.'),
                                ]),

                            Section::make('Wholesale pricing')
                                ->description('A lower unit price that activates when the customer reaches the minimum quantity.')
                                ->icon('heroicon-o-currency-bangladeshi')
                                ->schema([
                                    Toggle::make('is_wholesale')
                                        ->label('Available for wholesale')
                                        ->default(false)
                                        ->live(),

                                    TextInput::make('wholesale_min_quantity')
                                        ->label('Minimum quantity')
                                        ->numeric()
                                        ->minValue(2)
                                        ->maxValue(10000)
                                        ->default(10)
                                        ->required(fn ($get) => (bool) $get('is_wholesale'))
                                        ->visible(fn ($get) => (bool) $get('is_wholesale')),

                                    TextInput::make('wholesale_price')
                                        ->label('Wholesale unit price')
                                        ->prefix('à§³')
                                        ->numeric()
                                        ->minValue(0.01)
                                        ->required(fn ($get) => (bool) $get('is_wholesale') && $get('product_type') !== 'variable')
                                        ->visible(fn ($get) => (bool) $get('is_wholesale') && $get('product_type') !== 'variable')
                                        ->formatStateUsing(fn ($state) => $state === null ? null : Money::toTaka((int) $state))
                                        ->dehydrateStateUsing(fn ($state) => blank($state) ? null : Money::toPoisha($state)),
                                ]),
                        ])->columnSpan(['xl' => 1]),
                    ]),
            ]);
    }

    /**
     * @param array<mixed> $state
     * @param array<int, array{name?: mixed, values?: mixed}> $options
     */
    public static function attributeRows(array $state, array $options): array
    {
        $attributes = self::rowsToAttributes($state);
        $rows = [];

        foreach ($options as $option) {
            $name = trim((string) ($option['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $value = collect($attributes)->first(
                fn ($value, $existingName) => mb_strtolower(trim((string) $existingName)) === mb_strtolower($name),
            );
            $rows[] = ['name' => $name, 'value' => filled($value) ? $value : null];
        }

        if ($rows === [] && ! array_is_list($state)) {
            foreach ($state as $name => $value) {
                $rows[] = ['name' => $name, 'value' => $value];
            }
        }

        return $rows;
    }

    /**
     * @param array<mixed> $rows
     * @param array<int, array{name?: mixed, values?: mixed}> $options
     */
    public static function rowsToAttributes(array $rows, array $options = []): array
    {
        $containsFormRows = collect($rows)->contains(
            fn ($row) => is_array($row) && (array_key_exists('name', $row) || array_key_exists('value', $row)),
        );

        if (! $containsFormRows) {
            return $rows;
        }

        $attributes = [];
        foreach (array_values($rows) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ($options[$index]['name'] ?? '')));
            $value = trim((string) ($row['value'] ?? ''));
            if ($name !== '' && $value !== '') {
                $attributes[$name] = $value;
            }
        }

        return $attributes;
    }

    /** @param array<int, array{name?: mixed, values?: mixed}> $options */
    public static function valuesForOption(string $name, array $options): array
    {
        $option = collect($options)->first(
            fn ($option) => mb_strtolower(trim((string) ($option['name'] ?? ''))) === mb_strtolower(trim($name)),
        );

        return collect((array) ($option['values'] ?? []))
            ->mapWithKeys(fn ($value) => [(string) $value => (string) $value])
            ->all();
    }

    /**
     * @param array<mixed> $variants
     * @param array<int, array{name?: mixed, values?: mixed}> $options
     */
    public static function syncVariantOptionRows(array $variants, array $options): array
    {
        foreach ($variants as &$variant) {
            if (is_array($variant)) {
                $variant['attributes'] = self::attributeRows((array) ($variant['attributes'] ?? []), $options);
            }
        }

        return $variants;
    }
}
