<?php

namespace App\Filament\Resources\Menus;

use App\Models\Menu;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 80;

    protected static ?string $navigationLabel = 'Menus';

    public const LOCATIONS = [
        'header' => 'Header (Main navigation)',
        'footer_support' => 'Footer — Support column',
        'footer_account' => 'Footer — Account column',
        'footer_quick' => 'Footer — Quick links column',
        'mobile' => 'Mobile drawer',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Menu configuration')
                    ->description('Choose the menu title and where it renders on the storefront.')
                    ->icon('heroicon-o-bars-3')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Menu name')
                            ->placeholder('e.g. Main Navigation')
                            ->prefixIcon('heroicon-o-tag')
                            ->required(),

                        Select::make('location')
                            ->label('Display location')
                            ->prefixIcon('heroicon-o-map-pin')
                            ->native(false)
                            ->required()
                            ->options(self::LOCATIONS)
                            ->unique(ignoreRecord: true)
                            ->helperText('Each storefront location can hold one menu.'),
                    ]),

                Section::make('Menu links')
                    ->description('Drag items to reorder how they appear in the navigation.')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->hiddenLabel()
                            ->columns(6)
                            ->reorderable()
                            ->collapsible()
                            ->orderColumn('sort_order')
                            ->addActionLabel('Add menu link')
                            ->itemLabel(fn (array $state) => ($state['label'] ?? 'New link').(! empty($state['url']) ? " ({$state['url']})" : ''))
                            ->schema([
                                TextInput::make('label')
                                    ->label('Link text')
                                    ->placeholder('e.g. Shop Now')
                                    ->prefixIcon('heroicon-o-bars-2')
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('url')
                                    ->label('Destination URL')
                                    ->prefixIcon('heroicon-o-link')
                                    ->required()
                                    ->columnSpan(2)
                                    ->placeholder('/products or https://example.com')
                                    ->helperText('Start with / for an internal page.'),

                                Toggle::make('is_active')
                                    ->label('Visible')
                                    ->default(true)
                                    ->columnSpan(1),

                                Toggle::make('open_in_new_tab')
                                    ->label('New tab')
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Menu Name')
                    ->weight('bold')
                    ->icon('heroicon-m-bars-3')
                    ->searchable(),

                TextColumn::make('location')
                    ->label('Storefront Location')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => self::LOCATIONS[$state] ?? $state),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Links')
                    ->badge()
                    ->color('info'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
