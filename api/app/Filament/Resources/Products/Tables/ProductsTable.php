<?php

namespace App\Filament\Resources\Products\Tables;

use App\Support\Money;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->size(48),

                TextColumn::make('title')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn ($record) => $record->brand),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('product_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'variable' ? 'Variable' : 'Simple')
                    ->color(fn (string $state) => $state === 'variable' ? 'info' : 'gray'),

                TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => Money::format((int) $state))
                    ->description(fn ($record) => $record->isVariable()
                        ? 'Starting price'
                        : ($record->discount_percentage > 0
                        ? 'Sells at '.Money::format($record->discountedPrice())
                        : null)),

                TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state) => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('sold_count')
                    ->label('Sold')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Live')
                    ->boolean(),

                IconColumn::make('is_wholesale')
                    ->label('Wholesale')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('is_flash_sale')
                    ->label('Flash')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')->label('Published'),
                TernaryFilter::make('is_featured')->label('Featured'),
                TernaryFilter::make('is_wholesale')->label('Wholesale'),

                Filter::make('out_of_stock')
                    ->label('Out of stock')
                    ->query(fn (Builder $query) => $query->where('stock', 0)),

                Filter::make('low_stock')
                    ->label('Low stock (1-5)')
                    ->query(fn (Builder $query) => $query->whereBetween('stock', [1, 5])),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('View live')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Product $record) => rtrim(config('services.storefront.url'), '/')."/products/{$record->slug}")
                    ->openUrlInNewTab()
                    // An unpublished product 404s on the storefront, so there
                    // is nothing worth opening.
                    ->visible(fn (Product $record) => $record->is_active),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
