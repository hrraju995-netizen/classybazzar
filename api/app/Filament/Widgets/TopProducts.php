<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Support\Money;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopProducts extends TableWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected static ?string $heading = 'Top Selling Products';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg'      => 3,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('sold_count', '>', 0)
                    ->orderByDesc('sold_count')
                    ->limit(5)
            )
            ->paginated(false)
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=P&color=fff&background=eb6e1b'),

                TextColumn::make('title')
                    ->label('Product')
                    ->weight('bold')
                    ->limit(35)
                    ->description(fn (Product $record) => $record->category?->name),

                TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => Money::format((int) $state))
                    ->color('success'),

                TextColumn::make('sold_count')
                    ->label('Sold')
                    ->badge()
                    ->color('info'),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0  => 'danger',
                        $state <= 5  => 'warning',
                        default      => 'success',
                    }),
            ]);
    }
}

