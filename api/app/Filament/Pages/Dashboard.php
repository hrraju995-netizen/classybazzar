<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'lg' => 6];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\WelcomeBanner::class,
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\RevenueChart::class,
            \App\Filament\Widgets\OrderStatusChart::class,
            \App\Filament\Widgets\TopProducts::class,
            \App\Filament\Widgets\LatestOrders::class,
        ];
    }
}
