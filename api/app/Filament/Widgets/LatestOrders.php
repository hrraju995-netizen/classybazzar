<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected static ?string $heading = 'Recent Orders';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg'      => 3,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest()->limit(8))
            ->paginated(false)
            ->striped()
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->icon('heroicon-m-hashtag')
                    ->iconColor('gray'),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->icon('heroicon-m-user')
                    ->iconColor('gray')
                    ->description(fn ($record) => $record?->phone),

                TextColumn::make('total')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => Money::format((int) $state))
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper($state ?? 'COD'))
                    ->color(fn ($state) => match ($state) {
                        'bkash'       => 'danger',
                        'nagad'       => 'warning',
                        'sslcommerz'  => 'info',
                        default       => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Order::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'pending'                        => 'gray',
                        'confirmed', 'processing'        => 'info',
                        'in_transit', 'out_for_delivery'  => 'warning',
                        'delivered'                       => 'success',
                        default                           => 'danger',
                    })
                    ->icon(fn (string $state) => match ($state) {
                        'pending'          => 'heroicon-m-clock',
                        'confirmed'        => 'heroicon-m-check',
                        'processing'       => 'heroicon-m-cog-6-tooth',
                        'in_transit'       => 'heroicon-m-truck',
                        'out_for_delivery' => 'heroicon-m-map-pin',
                        'delivered'        => 'heroicon-m-check-badge',
                        'cancelled'        => 'heroicon-m-x-circle',
                        'returned'         => 'heroicon-m-arrow-uturn-left',
                        default            => null,
                    }),

                TextColumn::make('created_at')
                    ->label('Placed')
                    ->since()
                    ->color('gray')
                    ->icon('heroicon-m-calendar')
                    ->iconColor('gray'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),

                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('info')
                    ->url(fn (Order $record) => route('admin.orders.invoice-pdf', ['order' => $record])),
            ]);
    }
}
