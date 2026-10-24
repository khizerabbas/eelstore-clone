<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Filament\Resources\Products\ProductResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $new       = Order::where('status', OrderStatus::New)->count();
        $contacted = Order::where('status', OrderStatus::Contacted)->count();
        $today     = Order::whereDate('created_at', today())->count();

        $deliveredThisMonth = Order::where('status', OrderStatus::Delivered)
            ->where('delivered_at', '>=', now()->startOfMonth())
            ->sum('subtotal');

        $lowStock = Product::where('is_active', true)->where('stock', '<', 5)->count();

        $ordersUrl = OrderResource::getUrl('index');

        return [
            Stat::make('New requests', $new)
                ->description($new ? 'Call or WhatsApp these customers' : 'All caught up')
                ->color($new ? 'warning' : 'success')
                ->url($ordersUrl . '?tab=new'),
            Stat::make('Waiting for customer', $contacted)
                ->description('Contacted, not confirmed yet')
                ->color('info')
                ->url($ordersUrl . '?tab=contacted'),
            Stat::make('Requests today', $today),
            Stat::make('Delivered this month', 'Rs ' . number_format((float) $deliveredThisMonth))
                ->description('Cash collected on delivery')
                ->color('success'),
            Stat::make('Low stock products', $lowStock)
                ->description('Visible products with under 5 units')
                ->color($lowStock ? 'danger' : 'gray')
                ->url(ProductResource::getUrl('index')),
        ];
    }
}
