<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Support\Money;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])
                    ->schema([
                        Group::make([
                            Section::make('Coupon code & discount')
                                ->description('Define the promo code customers enter at checkout and how much it discounts.')
                                ->icon('heroicon-o-ticket')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('code')
                                        ->label('Promo code')
                                        ->placeholder('e.g. SAVE20')
                                        ->prefixIcon('heroicon-o-hashtag')
                                        ->required()
                                        ->maxLength(50)
                                        ->unique(ignoreRecord: true),

                                    Select::make('type')
                                        ->label('Discount type')
                                        ->options([
                                            'percent' => 'Percentage (%)',
                                            'fixed' => 'Fixed amount (৳)',
                                        ])
                                        ->default('percent')
                                        ->native(false)
                                        ->live()
                                        ->required(),

                                    TextInput::make('value')
                                        ->label('Discount value')
                                        ->prefix(fn ($get) => $get('type') === 'fixed' ? '৳' : null)
                                        ->suffix(fn ($get) => $get('type') === 'percent' ? '%' : null)
                                        ->required()
                                        ->numeric()
                                        ->minValue(0.01),
                                ]),

                            Section::make('Spend & usage limits')
                                ->description('Optional minimum order requirements and total redemption caps.')
                                ->icon('heroicon-o-adjustments-horizontal')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('min_order_amount')
                                        ->label('Minimum order amount')
                                        ->prefix('৳')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->formatStateUsing(fn ($state) => $state === null ? 0 : Money::toTaka((int) $state))
                                        ->dehydrateStateUsing(fn ($state) => Money::toPoisha($state ?? 0))
                                        ->helperText('Set 0 for no minimum spend.'),

                                    TextInput::make('max_discount_amount')
                                        ->label('Maximum discount cap')
                                        ->prefix('৳')
                                        ->numeric()
                                        ->formatStateUsing(fn ($state) => $state === null ? null : Money::toTaka((int) $state))
                                        ->dehydrateStateUsing(fn ($state) => blank($state) ? null : Money::toPoisha($state))
                                        ->helperText('Optional ceiling for percentage discounts.'),

                                    TextInput::make('usage_limit')
                                        ->label('Total usage limit')
                                        ->prefixIcon('heroicon-o-users')
                                        ->numeric()
                                        ->placeholder('Unlimited')
                                        ->helperText('Leave blank for unlimited redemptions.'),

                                    TextInput::make('used_count')
                                        ->label('Times used')
                                        ->prefixIcon('heroicon-o-check-circle')
                                        ->required()
                                        ->numeric()
                                        ->default(0),
                                ]),
                        ])->columnSpan(['xl' => 2]),

                        Group::make([
                            Section::make('Status & validity')
                                ->description('Control when this coupon is active.')
                                ->icon('heroicon-o-calendar-days')
                                ->schema([
                                    Toggle::make('is_active')
                                        ->label('Coupon active')
                                        ->default(true)
                                        ->helperText('Disable to pause this code immediately.'),

                                    DateTimePicker::make('starts_at')
                                        ->label('Valid from')
                                        ->native(false)
                                        ->placeholder('Immediately'),

                                    DateTimePicker::make('expires_at')
                                        ->label('Expires at')
                                        ->native(false)
                                        ->placeholder('Never expires'),
                                ]),
                        ])->columnSpan(['xl' => 1]),
                    ]),
            ]);
    }
}
