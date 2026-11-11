<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    protected ?string $heading = 'Orders by Status';

    protected ?string $description = 'Current distribution of all orders';

    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg'      => 2,
    ];

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $statuses = [
            'pending'          => ['label' => 'Pending',           'color' => 'rgb(156, 163, 175)'],
            'confirmed'        => ['label' => 'Confirmed',         'color' => 'rgb(96, 165, 250)'],
            'processing'       => ['label' => 'Processing',        'color' => 'rgb(59, 130, 246)'],
            'in_transit'       => ['label' => 'In Transit',        'color' => 'rgb(251, 191, 36)'],
            'out_for_delivery' => ['label' => 'Out for Delivery',  'color' => 'rgb(251, 146, 60)'],
            'delivered'        => ['label' => 'Delivered',         'color' => 'rgb(34, 197, 94)'],
            'cancelled'        => ['label' => 'Cancelled',         'color' => 'rgb(239, 68, 68)'],
            'returned'         => ['label' => 'Returned',          'color' => 'rgb(168, 85, 247)'],
        ];

        $counts = [];
        $labels = [];
        $colors = [];

        foreach ($statuses as $status => $meta) {
            $count = Order::where('status', $status)->count();
            if ($count > 0) {
                $counts[] = $count;
                $labels[] = $meta['label'];
                $colors[] = $meta['color'];
            }
        }

        if (empty($counts)) {
            $counts = [1];
            $labels = ['No Orders Yet'];
            $colors = ['rgb(229, 231, 235)'];
        }

        return [
            'datasets' => [
                [
                    'data'            => $counts,
                    'backgroundColor' => $colors,
                    'borderWidth'     => 0,
                    'hoverOffset'     => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels'   => [
                        'usePointStyle' => true,
                        'pointStyle'    => 'circle',
                        'padding'       => 16,
                        'font'          => ['size' => 12],
                    ],
                ],
            ],
            'cutout' => '65%',
        ];
    }
}
