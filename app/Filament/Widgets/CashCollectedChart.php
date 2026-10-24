<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Widgets\Concerns\WeeklyBuckets;
use App\Models\Order;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

// Dashboard (Owner only): value of delivered orders per week = cash collected on delivery
class CashCollectedChart extends ChartWidget
{
    use WeeklyBuckets;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Cash collected per week';

    protected ?string $description = 'Total of orders marked Delivered (cash on delivery), by delivery week.';

    protected ?string $maxHeight = '300px';

    public ?string $filter = '12';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isOwner();
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $delivered = Order::where('status', OrderStatus::Delivered)
            ->where('delivered_at', '>=', $this->firstWeekStart())
            ->get(['delivered_at', 'subtotal']);

        return [
            'datasets' => [
                [
                    'label'           => 'Rs collected',
                    'data'            => $this->perWeek($delivered, 'delivered_at', fn (Order $o) => (float) $o->subtotal),
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill'            => true,
                    'tension'         => 0.3,
                ],
            ],
            'labels' => $this->labels(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                scales: { y: { beginAtZero: true, ticks: { callback: (value) => 'Rs ' + Number(value).toLocaleString() } } },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => 'Rs ' + Number(ctx.parsed.y).toLocaleString() } },
                },
            }
        JS);
    }
}
