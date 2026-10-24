<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Widgets\CashCollectedChart;
use App\Filament\Widgets\RequestsPerWeekChart;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00', 'Asia/Karachi')); // a Thursday
    }

    private function data(string $widget, string $filter = '12'): array
    {
        $chart = new $widget();
        $chart->filter = $filter;

        return (fn () => $this->getData())->call($chart);
    }

    private function order(string $createdAt, ?string $deliveredAt = null, int $total = 1000): void
    {
        $order = Order::create(['customer_name' => 'A', 'customer_phone' => '+923001234567', 'subtotal' => $total]);
        $order->forceFill(['created_at' => $createdAt])->saveQuietly();

        if ($deliveredAt) {
            $order->forceFill(['status' => OrderStatus::Delivered, 'delivered_at' => $deliveredAt])->saveQuietly();
        }
    }

    public function test_requests_are_counted_per_monday_to_sunday_week(): void
    {
        $this->order('2026-10-19 09:00');                       // this week (Mon)
        $this->order('2026-10-22 08:00', '2026-10-22 18:00');   // this week, delivered this week
        $this->order('2026-10-18 23:30');                       // last week (Sun)
        $this->order('2026-01-01 10:00');                       // outside 12 weeks
        QuoteRequest::create(['name' => 'Q', 'phone' => '+923001234567', 'items' => 'x']);

        $data = $this->data(RequestsPerWeekChart::class);

        $this->assertCount(12, $data['labels']);
        $this->assertSame('19 Oct', end($data['labels']));
        [$orders, $delivered, $quotes] = array_column($data['datasets'], 'data');

        $this->assertSame([1, 2], array_slice($orders, -2));    // last week 1, this week 2
        $this->assertSame(3, array_sum($orders));               // January order not counted
        $this->assertSame(1, end($delivered));
        $this->assertSame(1, end($quotes));
    }

    public function test_range_filter(): void
    {
        $this->assertCount(52, $this->data(RequestsPerWeekChart::class, '52')['labels']);
        $this->assertCount(12, $this->data(RequestsPerWeekChart::class, 'nonsense')['labels']);
    }

    public function test_cash_collected_sums_delivered_orders_by_delivery_week(): void
    {
        $this->order('2026-10-10 10:00', '2026-10-20 15:00', 2500);
        $this->order('2026-10-12 10:00', '2026-10-21 15:00', 4000);
        $this->order('2026-10-21 10:00');                               // not delivered

        $data = $this->data(CashCollectedChart::class)['datasets'][0]['data'];

        $this->assertEquals(6500, end($data));
        $this->assertEquals(6500, array_sum($data));
    }

    public function test_cash_chart_is_owner_only_and_dashboard_renders(): void
    {
        $this->actingAs(User::factory()->create());
        $this->assertFalse(CashCollectedChart::canView());
        $this->get('/admin')->assertOk();

        $this->actingAs(User::factory()->owner()->create());
        $this->assertTrue(CashCollectedChart::canView());
        $this->get('/admin')->assertOk();
    }
}
