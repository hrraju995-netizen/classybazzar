<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadInvoicePdf')
                ->label('Download Invoice PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn (Order $record): string => route('admin.orders.invoice-pdf', ['order' => $record])),

            Action::make('printInvoicePdf')
                ->label('Print / View PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (Order $record): string => route('admin.orders.invoice-pdf', ['order' => $record, 'inline' => 1]))
                ->openUrlInNewTab(),

            Action::make('updateStatus')
                ->label('Update status')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->schema([
                    Select::make('status')
                        ->label('New status')
                        ->options(Order::STATUS_LABELS)
                        ->required()
                        ->default(fn (Order $record) => $record->status),

                    TextInput::make('carrier')
                        ->label('Courier')
                        ->placeholder('Steadfast / Pathao / RedX')
                        ->default(fn (Order $record) => $record->carrier),

                    TextInput::make('tracking_code')
                        ->label('Consignment ID')
                        ->default(fn (Order $record) => $record->tracking_code),

                    Textarea::make('description')
                        ->label('Note for the customer')
                        ->rows(2)
                        ->helperText('Shown on the order tracking page.'),
                ])
                ->action(function (Order $record, array $data, OrderService $orders): void {
                    $record->fill(array_filter([
                        'carrier' => $data['carrier'] ?? null,
                        'tracking_code' => $data['tracking_code'] ?? null,
                    ]))->save();

                    $orders->updateStatus($record, $data['status'], $data['description'] ?? null);
                })
                ->successNotificationTitle('Order status updated'),
        ];
    }
}
