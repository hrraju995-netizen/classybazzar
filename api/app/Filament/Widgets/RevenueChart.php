<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\Money;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue Overview';

    protected ?string $description = 'Monthly revenue for the last 6 months';

    protected static ?int $sort = -1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg'      => 4,
    ];

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $earned = fn ($query) => $query->whereNotIn('status', ['cancelled', 'returned']);

        $months = collect(range(5, 0))->map(function ($monthsAgo) use ($earned) {
            $start = now()->subMonths($monthsAgo)->startOfMonth();
            $end   = now()->subMonths($monthsAgo)->endOfMonth();

            $revenue = (int) Order::query()
                ->tap($earned)
                ->whereBetween('created_at', [$start, $end])
                ->sum('total');

            return [
                'label'   => $start->format('M Y'),
                'revenue' => round($revenue / 100, 2),
            ];
        });

        return [
            'datasets' => [
                [
                    'label'                => 'Revenue (৳)',
                    'data'                 => $months->pluck('revenue')->toArray(),
                    'backgroundColor'      => 'rgba(235, 110, 27, 0.12)',
                    'borderColor'          => 'rgb(235, 110, 27)',
                    'borderWidth'          => 2.5,
                    'fill'                 => true,
                    'tension'              => 0.4,
                    'pointBackgroundColor' => 'rgb(235, 110, 27)',
                    'pointBorderColor'     => '#fff',
                    'pointBorderWidth'     => 2,
                    'pointRadius'          => 5,
                    'pointHoverRadius'     => 7,
                ],
            ],
            'labels' => $months->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(ctx){ return "৳ " + ctx.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid'        => ['color' => 'rgba(0,0,0,0.04)'],
                    'ticks'       => ['color' => '#6b7280'],
                ],
                'x' => [
                    'grid'  => ['display' => false],
                    'ticks' => ['color' => '#6b7280'],
                ],
            ],
        ];
    }
}
