<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /**
     * Tabs mirror how a shop actually works a day: new orders first, then
     * what is out with the courier, then what is done.
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),

            'new' => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'pending'))
                ->badge(Order::where('status', 'pending')->count())
                ->badgeColor('warning'),

            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', ['confirmed', 'processing']))
                ->badge(Order::whereIn('status', ['confirmed', 'processing'])->count()),

            'shipped' => Tab::make('Shipped')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', ['in_transit', 'out_for_delivery']))
                ->badge(Order::whereIn('status', ['in_transit', 'out_for_delivery'])->count()),

            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'delivered')),

            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', ['cancelled', 'returned'])),
        ];
    }
}
