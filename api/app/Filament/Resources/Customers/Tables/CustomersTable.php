<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Support\Money;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('primary')
                    ->description(fn ($record): string => $record->email ?: 'No email set'),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone')
                    ->iconColor('gray')
                    ->placeholder('Not set'),

                TextColumn::make('address')
                    ->label('Address / City')
                    ->searchable()
                    ->limit(36)
                    ->description(fn ($record): ?string => $record->city)
                    ->placeholder('No address saved'),

                TextColumn::make('auth_provider')
                    ->label('Sign-in')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'google' => 'Google',
                        'phone' => 'Phone',
                        default => 'Email',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'google' => 'info',
                        'phone' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('orders_sum_total')
                    ->label('Total spent')
                    ->weight('bold')
                    ->color('success')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => Money::format((int) ($state ?? 0))),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('auth_provider')
                    ->label('Sign-in method')
                    ->options([
                        'email' => 'Email',
                        'phone' => 'Phone Number',
                        'google' => 'Google Account',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

