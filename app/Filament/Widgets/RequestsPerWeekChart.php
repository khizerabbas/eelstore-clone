<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Widgets\Concerns\WeeklyBuckets;
use App\Models\Order;
use App\Models\QuoteRequest;
use Filament\Widgets\ChartWidget;

// Dashboard: order requests, deliveries and quote requests per week
class RequestsPerWeekChart extends ChartWidget
{
    use WeeklyBuckets;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Requests per week';

    protected ?string $description = 'Order requests received, orders delivered and quote requests – Monday to Sunday.';

    protected ?string $maxHeight = '300px';

    public ?string $filter = '12';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $from = $this->firstWeekStart();

        $orders    = Order::where('created_at', '>=', $from)->get(['created_at']);
        $delivered = Order::where('status', OrderStatus::Delivered)->where('delivered_at', '>=', $from)->get(['delivered_at']);
        $quotes    = QuoteRequest::where('created_at', '>=', $from)->get(['created_at']);

        return [
            'datasets' => [
                [
                    'label'           => 'Order requests',
                    'data'            => $this->perWeek($orders, 'created_at'),
                    'backgroundColor' => '#f59e0b',
                    'borderRadius'    => 4,
                ],
                [
                    'label'           => 'Delivered',
                    'data'            => $this->perWeek($delivered, 'delivered_at'),
                    'backgroundColor' => '#10b981',
                    'borderRadius'    => 4,
                ],
                [
                    'label'           => 'Quote requests',
                    'data'            => $this->perWeek($quotes, 'created_at'),
                    'backgroundColor' => '#0ea5e9',
                    'borderRadius'    => 4,
                ],
            ],
            'labels' => $this->labels(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales'  => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
