<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])
                    ->schema([
                        Group::make([
                            Section::make('Category details')
                                ->description('Basic information and URL slug for this product category.')
                                ->icon('heroicon-o-squares-2x2')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Category name')
                                        ->placeholder('e.g. Electronics & Gadgets')
                                        ->prefixIcon('heroicon-o-tag')
                                        ->required()
                                        ->maxLength(160),

                                    TextInput::make('slug')
                                        ->label('URL Slug')
                                        ->placeholder('e.g. electronics-gadgets')
                                        ->prefixIcon('heroicon-o-link')
                                        ->required()
                                        ->maxLength(180)
                                        ->unique(ignoreRecord: true),

                                    Textarea::make('description')
                                        ->label('Description')
                                        ->placeholder('Short summary shown on category pages...')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpan(['xl' => 2]),

                        Group::make([
                            Section::make('Category image & display')
                                ->description('Thumbnail icon/photo and display order on the storefront.')
                                ->icon('heroicon-o-photo')
                                ->schema([
                                    FileUpload::make('image')
                                        ->label('Category thumbnail')
                                        ->image()
                                        ->disk('public')
                                        ->directory('categories')
                                        ->imagePreviewHeight('150')
                                        ->helperText('Square image recommended (JPG, PNG, WebP).'),

                                    TextInput::make('sort_order')
                                        ->label('Display order')
                                        ->prefixIcon('heroicon-o-arrows-up-down')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('Lower numbers appear first in menus.'),

                                    Toggle::make('is_active')
                                        ->label('Active on storefront')
                                        ->default(true)
                                        ->helperText('Hidden categories will not appear in navigation.'),
                                ]),
                        ])->columnSpan(['xl' => 1]),
                    ]),
            ]);
    }
}
