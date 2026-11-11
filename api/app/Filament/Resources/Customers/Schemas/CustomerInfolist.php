<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])->schema([
                    Group::make([
                        Section::make('Customer details')
                            ->description('Personal profile, contact numbers, and saved delivery address.')
                            ->icon('heroicon-o-identification')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Full name')
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('auth_provider')
                                    ->label('Sign-in method')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'google' => 'Google Sign-In',
                                        'phone' => 'Phone Number',
                                        default => 'Email & Password',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'google' => 'info',
                                        'phone' => 'success',
                                        default => 'primary',
                                    }),

                                TextEntry::make('phone')
                                    ->label('Phone number')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->placeholder('Not provided'),

                                TextEntry::make('email')
                                    ->label('Email address')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->placeholder('Not provided'),

                                TextEntry::make('city')
                                    ->label('City / District')
                                    ->icon('heroicon-o-building-office-2')
                                    ->placeholder('Not set'),

                                TextEntry::make('created_at')
                                    ->label('Registered on')
                                    ->icon('heroicon-o-calendar')
                                    ->dateTime('d M Y, h:i A'),

                                TextEntry::make('address')
                                    ->label('Saved delivery address')
                                    ->icon('heroicon-o-map-pin')
                                    ->placeholder('No address saved yet')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Order history')
                            ->description('All orders placed by this customer.')
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                RepeatableEntry::make('orders')
                                    ->hiddenLabel()
                                    ->columns(5)
                                    ->schema([
                                        TextEntry::make('order_number')
                                            ->label('Order #')
                                            ->weight('bold')
                                            ->color('primary')
                                            ->copyable(),

                                        TextEntry::make('status')
                                            ->label('Status')
                                            ->badge()
                                            ->formatStateUsing(fn (string $state): string => Order::STATUS_LABELS[$state] ?? $state)
                                            ->color(fn (string $state): string => match ($state) {
                                                'delivered' => 'success',
                                                'cancelled', 'returned' => 'danger',
                                                'in_transit', 'out_for_delivery' => 'warning',
                                                'confirmed', 'processing' => 'info',
                                                default => 'gray',
                                            }),

                                        TextEntry::make('payment_method')
                                            ->label('Payment')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => strtoupper((string) ($state ?: 'COD'))),

                                        TextEntry::make('total')
                                            ->label('Total')
                                            ->weight('bold')
                                            ->color('success')
                                            ->formatStateUsing(fn ($state): string => Money::format((int) $state)),

                                        TextEntry::make('created_at')
                                            ->label('Placed on')
                                            ->dateTime('d M Y, h:i A'),
                                    ]),
                            ]),
                    ])->columnSpan(['xl' => 2]),

                    Group::make([
                        Section::make('Customer summary')
                            ->description('Lifetime activity & spending metrics.')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                TextEntry::make('orders_total_count')
                                    ->label('Total orders placed')
                                    ->state(fn (User $record): int => $record->orders()->count())
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('primary'),

                                TextEntry::make('lifetime_spent')
                                    ->label('Total amount spent')
                                    ->state(fn (User $record): string => Money::format((int) $record->orders()->whereNotIn('status', ['cancelled', 'returned'])->sum('total')))
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('success'),

                                TextEntry::make('last_order_date')
                                    ->label('Last order placed')
                                    ->state(function (User $record): string {
                                        $latest = $record->orders()->latest()->first();

                                        return $latest?->created_at?->format('d M Y, h:i A') ?? 'No orders yet';
                                    }),

                                TextEntry::make('wishlist_count')
                                    ->label('Wishlist items')
                                    ->state(fn (User $record): int => $record->wishlistItems()->count()),
                            ]),
                    ])->columnSpan(['xl' => 1]),
                ]),
            ]);
    }
}

