<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])
                    ->schema([
                        Group::make([
                            Section::make('Page content')
                                ->description('Create or edit informational pages such as About Us, Privacy Policy, or Terms.')
                                ->icon('heroicon-o-document-text')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('title')
                                        ->label('Page title')
                                        ->placeholder('e.g. Return & Refund Policy')
                                        ->prefixIcon('heroicon-o-document')
                                        ->required()
                                        ->maxLength(200),

                                    TextInput::make('slug')
                                        ->label('URL Slug')
                                        ->placeholder('auto-generated-from-title')
                                        ->prefixIcon('heroicon-o-link')
                                        ->maxLength(220)
                                        ->unique(ignoreRecord: true)
                                        ->helperText('Leave blank to build it from the title. The page will live at /<slug>.'),

                                    RichEditor::make('content')
                                        ->label('Body content')
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpan(['xl' => 2]),

                        Group::make([
                            Section::make('Visibility & SEO')
                                ->description('Search engine metadata and publication status.')
                                ->icon('heroicon-o-globe-alt')
                                ->schema([
                                    Toggle::make('is_published')
                                        ->label('Published on storefront')
                                        ->default(true)
                                        ->helperText('Unpublished pages return 404 to visitors.'),

                                    TextInput::make('meta_title')
                                        ->label('SEO Meta Title')
                                        ->prefixIcon('heroicon-o-magnifying-glass')
                                        ->maxLength(200)
                                        ->helperText('Falls back to the page title when blank.'),

                                    Textarea::make('meta_description')
                                        ->label('SEO Meta Description')
                                        ->rows(4)
                                        ->maxLength(500)
                                        ->placeholder('Brief summary for search results...'),
                                ]),
                        ])->columnSpan(['xl' => 1]),
                    ]),
            ]);
    }
}
