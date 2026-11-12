<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use App\Services\Couriers\CourierException;
use App\Services\Couriers\CourierManager;
use App\Services\OrderService;
use App\Support\Media;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label('')
                    ->state(function (Order $record): ?string {
                        $item = $record->items()->with(['variant', 'product'])->first();
                        if (! $item) {
                            return null;
                        }

                        return Media::url(
                            $item->thumbnail
                                ?: $item->variant?->image
                                ?: $item->product?->thumbnail
                        );
                    })
                    ->square()
                    ->size(44)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->defaultImageUrl('https://placehold.co/44x44?text=Order'),

                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->copyable()
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->phone),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->badge()
                    ->color('gray')
                    ->counts('items'),

                TextColumn::make('total')
                    ->weight('bold')
                    ->color('success')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => Money::format((int) $state)),

                TextColumn::make('shipping_area')
                    ->label('Area')
                    ->badge()
                    ->color(fn ($state) => $state === 'inside' ? 'info' : 'warning')
                    ->formatStateUsing(fn ($state) => $state === 'inside' ? 'Inside Dhaka' : 'Outside Dhaka')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Order::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'gray',
                        'confirmed', 'processing' => 'info',
                        'in_transit', 'out_for_delivery' => 'warning',
                        'delivered' => 'success',
                        'cancelled', 'returned' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper((string) $state))
                    ->description(fn (Order $record) => $record->transaction_id ? "Trx: {$record->transaction_id}" : ($record->payment_number ? "From: {$record->payment_number}" : null))
                    ->toggleable(),

                TextColumn::make('courier')
                    ->badge()
                    ->placeholder('Not sent')
                    ->description(fn ($record) => $record->courier_consignment_id)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(Order::STATUS_LABELS)
                    ->multiple(),

                SelectFilter::make('shipping_area')
                    ->options(['inside' => 'Inside Dhaka', 'outside' => 'Outside Dhaka']),

                Filter::make('wholesale')
                    ->label('Contains wholesale items')
                    ->query(fn ($query) => $query->whereHas('items', fn ($items) => $items->where('pricing_type', 'wholesale'))),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('downloadPdf')
                    ->label('Invoice PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn (Order $record): string => route('admin.orders.invoice-pdf', ['order' => $record])),

                Action::make('sendToCourier')
                    ->label('Send to courier')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Order $record) => blank($record->courier_consignment_id)
                        && ! in_array($record->status, ['cancelled', 'returned', 'delivered'], true)
                        && count(app(CourierManager::class)->available()) > 0)
                    ->schema([
                        Select::make('courier')
                            ->label('Courier')
                            ->options(fn () => app(CourierManager::class)->available())
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('A consignment will be created and the order marked as in transit.')
                    ->action(function (Order $record, array $data, CourierManager $couriers) {
                        try {
                            $result = $couriers->send($record, $data['courier']);
                        } catch (CourierException $e) {
                            Notification::make()->danger()
                                ->title('Could not create the consignment')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()->success()
                            ->title('Consignment created')
                            ->body('Tracking: '.($result->trackingCode ?? $result->consignmentId))
                            ->send();
                    }),

                Action::make('updateStatus')
                    ->label('Update status')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->schema([
                        Select::make('status')
                            ->label('New status')
                            ->options(Order::STATUS_LABELS)
                            ->required()
                            ->default(fn ($record) => $record->status),

                        TextInput::make('carrier')
                            ->label('Courier')
                            ->placeholder('Steadfast / Pathao / RedX')
                            ->default(fn ($record) => $record->carrier),

                        TextInput::make('tracking_code')
                            ->label('Consignment ID')
                            ->default(fn ($record) => $record->tracking_code),

                        Textarea::make('description')
                            ->label('Note for the customer')
                            ->rows(2)
                            ->helperText('Shown on the order tracking page.'),
                    ])
                    ->action(function (Order $record, array $data, OrderService $orders) {
                        $record->fill(array_filter([
                            'carrier' => $data['carrier'] ?? null,
                            'tracking_code' => $data['tracking_code'] ?? null,
                        ]))->save();

                        $orders->updateStatus($record, $data['status'], $data['description'] ?? null);
                    })
                    ->successNotificationTitle('Order status updated'),
            ]);
    }
}
