<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Media;
use App\Support\Money;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])->schema([
                    // ---------------- Main column ----------------
                    Group::make([
                        Section::make('Items')
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->hiddenLabel()
                                    ->columns(8)
                                    ->schema([
                                        // Falls back to the live product/variant image for
                                        // older orders placed before the snapshot was saved.
                                        ImageEntry::make('thumbnail')
                                            ->label('Image')
                                            ->state(fn (OrderItem $record) => Media::url(
                                                $record->thumbnail
                                                    ?: $record->variant?->image
                                                    ?: $record->product?->thumbnail
                                            ))
                                            ->imageSize(64)
                                            ->square()
                                            ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                                            ->defaultImageUrl('https://placehold.co/64x64?text=No+image'),
                                        TextEntry::make('title')->label('Product')->weight('bold')->columnSpan(2),
                                        TextEntry::make('variant_name')->label('Variation')->placeholder('Simple product'),
                                        TextEntry::make('quantity')->label('Qty'),
                                        TextEntry::make('price')
                                            ->label('Unit price')
                                            ->formatStateUsing(fn ($state) => Money::format((int) $state)),
                                        TextEntry::make('pricing_type')
                                            ->label('Sale type')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => $state === 'wholesale' ? 'Wholesale' : 'Retail')
                                            ->color(fn ($state) => $state === 'wholesale' ? 'success' : 'gray'),
                                        TextEntry::make('subtotal')
                                            ->label('Line total')
                                            ->weight('bold')
                                            ->formatStateUsing(fn ($state) => Money::format((int) $state)),
                                    ]),
                            ]),

                        Section::make('Timeline')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                RepeatableEntry::make('events')
                                    ->hiddenLabel()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('title')->weight('bold'),
                                        TextEntry::make('description')->placeholder('-'),
                                        TextEntry::make('happened_at')->label('When')->dateTime('d M Y, h:i A'),
                                    ]),
                            ]),
                    ])->columnSpan(['xl' => 2]),

                    // ---------------- Side column ----------------
                    Group::make([
                        Section::make('Customer')
                            ->icon('heroicon-o-user')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('customer_name')->label('Name')->weight('bold'),
                                TextEntry::make('phone')->icon('heroicon-o-phone')->copyable(),
                                TextEntry::make('email')->icon('heroicon-o-envelope')->placeholder('Not given')->columnSpanFull(),
                                TextEntry::make('address')->icon('heroicon-o-map-pin')->columnSpanFull(),
                                TextEntry::make('shipping_area')
                                    ->label('Delivery area')
                                    ->formatStateUsing(fn ($state) => $state === 'inside' ? 'Inside Dhaka' : 'Outside Dhaka'),
                                TextEntry::make('note')->label('Customer note')->placeholder('None'),
                            ]),

                        Section::make('Delivery & Payment')
                            ->icon('heroicon-o-truck')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => Order::STATUS_LABELS[$state] ?? $state),
                                TextEntry::make('estimated_delivery_at')->label('Estimated delivery')->date('d M Y'),
                                TextEntry::make('carrier')->placeholder('Not assigned'),
                                TextEntry::make('tracking_code')->label('Consignment ID')->placeholder('Not assigned'),
                                TextEntry::make('payment_method')->label('Payment Method')->badge()->formatStateUsing(fn ($state) => strtoupper((string) $state)),
                                TextEntry::make('payment_status')->label('Payment Status')->badge(),
                                TextEntry::make('payment_number')->label('Sender Number')->placeholder('-')->copyable(),
                                TextEntry::make('transaction_id')->label('Transaction ID (TrxID)')->placeholder('-')->copyable(),
                            ]),

                        Section::make('Totals')
                            ->icon('heroicon-o-banknotes')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('subtotal')->formatStateUsing(fn ($state) => Money::format((int) $state)),
                                TextEntry::make('shipping_charge')->label('Shipping')->formatStateUsing(fn ($state) => Money::format((int) $state)),
                                TextEntry::make('discount')->formatStateUsing(fn ($state) => Money::format((int) $state)),
                                TextEntry::make('total')->size('lg')->weight('bold')->color('primary')->formatStateUsing(fn ($state) => Money::format((int) $state)),
                            ]),
                    ])->columnSpan(['xl' => 1]),
                ]),
            ]);
    }
}
