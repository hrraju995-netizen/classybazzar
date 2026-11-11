<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int|array|null
    {
        return 4;
    }

    protected function getStats(): array
    {
        // --- Revenue -------------------------------------------------
        $earned = fn ($query) => $query->whereNotIn('status', ['cancelled', 'returned']);

        $thisMonth = (int) Order::query()
            ->tap($earned)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total');

        $lastMonth = (int) Order::query()
            ->tap($earned)
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->sum('total');

        $change = $lastMonth > 0
            ? round((($thisMonth - $lastMonth) / $lastMonth) * 100)
            : null;

        // Last 7 days revenue for sparkline
        $revenueChart = collect(range(6, 0))->map(function ($daysAgo) use ($earned) {
            $date = now()->subDays($daysAgo);
            return (int) Order::query()
                ->tap($earned)
                ->whereDate('created_at', $date)
                ->sum('total');
        })->map(fn ($v) => $v / 100)->toArray(); // Convert to taka for chart

        // --- Orders -------------------------------------------------
        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', today())->count();
        $pending = Order::where('status', 'pending')->count();

        // Last 7 days orders for sparkline
        $ordersChart = collect(range(6, 0))->map(function ($daysAgo) {
            return Order::whereDate('created_at', now()->subDays($daysAgo))->count();
        })->toArray();

        // --- Stock --------------------------------------------------
        $totalProducts = Product::count();
        $outOfStock = Product::where('stock', 0)->count();
        $lowStock = Product::whereBetween('stock', [1, 5])->count();
        $activeProducts = Product::where('is_active', true)->count();

        // --- Customers ----------------------------------------------
        $totalCustomers = User::where('is_admin', false)->count();
        $newCustomersThisMonth = User::where('is_admin', false)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $unread = ContactMessage::where('is_read', false)->count();

        return [
            Stat::make('Total Revenue', Money::format($thisMonth))
                ->description($change === null
                    ? 'No sales last month'
                    : ($change >= 0 ? "↑ {$change}% vs last month" : '↓ ' . abs($change) . '% vs last month'))
                ->descriptionIcon($change !== null && $change >= 0
                    ? 'heroicon-m-arrow-trending-up'
                    : 'heroicon-m-arrow-trending-down')
                ->chart($revenueChart)
                ->color($change !== null && $change < 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => 'ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl',
                ]),

            Stat::make('Total Orders', number_format($totalOrders))
                ->description($todayOrders > 0 ? "{$todayOrders} orders today" : 'No orders today')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->chart($ordersChart)
                ->color('info')
                ->extraAttributes([
                    'class' => 'ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl',
                ]),

            Stat::make('Products', number_format($activeProducts) . '/' . number_format($totalProducts))
                ->description(
                    $outOfStock > 0
                        ? "⚠ {$outOfStock} out of stock" . ($lowStock > 0 ? ", {$lowStock} low" : '')
                        : ($lowStock > 0 ? "{$lowStock} running low" : 'All well stocked')
                )
                ->descriptionIcon($outOfStock > 0
                    ? 'heroicon-m-exclamation-triangle'
                    : 'heroicon-m-check-circle')
                ->color($outOfStock > 0 ? 'danger' : ($lowStock > 0 ? 'warning' : 'success'))
                ->extraAttributes([
                    'class' => 'ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl',
                ]),

            Stat::make('Customers', number_format($totalCustomers))
                ->description(
                    ($newCustomersThisMonth > 0 ? "+{$newCustomersThisMonth} this month" : 'No new this month')
                    . ($unread > 0 ? " · {$unread} messages" : '')
                )
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl',
                ]),
        ];
    }
}
