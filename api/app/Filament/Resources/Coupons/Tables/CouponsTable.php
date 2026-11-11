<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Promo Code')
                    ->badge()
                    ->color('primary')
                    ->weight('bold')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'percent' ? 'Percentage' : 'Fixed')
                    ->color(fn ($state) => $state === 'percent' ? 'info' : 'success'),

                TextColumn::make('value')
                    ->label('Discount')
                    ->weight('bold')
                    ->formatStateUsing(fn ($state, $record) => $record->type === 'percent'
                        ? rtrim(rtrim(number_format((float) $state, 2), '0'), '.').'%'
                        : '৳'.number_format((float) $state, 2))
                    ->sortable(),

                TextColumn::make('min_order_amount')
                    ->label('Min Spend')
                    ->formatStateUsing(fn ($state) => Money::format((int) $state))
                    ->sortable(),

                TextColumn::make('used_count')
                    ->label('Usage')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state, $record) => $record->usage_limit
                        ? "{$state} / {$record->usage_limit}"
                        : "{$state} / ∞")
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('d M Y')
                    ->placeholder('Never')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
