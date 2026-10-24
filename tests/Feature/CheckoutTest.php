<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\SendOwnerWhatsAppAlert;
use App\Mail\NewOrderRequest;
use App\Mail\OrderRequestReceived;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\WhatsApp\CallMeBotWhatsAppNotifier;
use App\Services\WhatsApp\WhatsAppNotifier;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['orders.admin_email' => 'owner@example.com']);
        Mail::fake();
        Bus::fake([SendOwnerWhatsAppAlert::class]);
    }

    private function makeProduct(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'wires'], ['name' => 'Wires']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name'        => 'Test Wire',
            'slug'        => 'test-wire-' . uniqid(),
            'price'       => 1000,
            'is_active'   => true,
        ], $attributes));
    }

    private function validForm(array $overrides = []): array
    {
        return array_merge([
            'name'    => 'Ali Khan',
            'phone'   => '0300 1234567',
            'email'   => '',
            'address' => '',
            'comment' => '',
        ], $overrides);
    }

    public function test_checkout_redirects_to_cart_when_empty(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('cart.index'));
    }

    public function test_checkout_page_shows_cart_and_is_noindex(): void
    {
        $product = $this->makeProduct(['name' => 'Copper Wire 2.5mm']);
        $this->post(route('cart.add', $product), ['quantity' => 2]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Copper Wire 2.5mm')
            ->assertSee('Rs 2,000')
            ->assertSee('noindex', false);
    }

    public function test_name_and_valid_phone_are_required(): void
    {
        $product = $this->makeProduct();
        $this->post(route('cart.add', $product));

        $this->post(route('checkout.store'), $this->validForm(['name' => '', 'phone' => '']))
            ->assertSessionHasErrors(['name', 'phone']);

        $this->post(route('checkout.store'), $this->validForm(['phone' => '12345']))
            ->assertSessionHasErrors(['phone']);

        $this->post(route('checkout.store'), $this->validForm(['email' => 'not-an-email']))
            ->assertSessionHasErrors(['email']);

        $this->assertSame(0, Order::count());
    }

    public function test_successful_request_creates_order_and_notifies_owner(): void
    {
        $wire = $this->makeProduct(['name' => 'Wire', 'price' => 2500, 'sale_price' => 2200, 'is_on_sale' => true, 'sku' => 'W-1']);
        $bulb = $this->makeProduct(['name' => 'Bulb', 'price' => 300]);

        $this->post(route('cart.add', $wire), ['quantity' => 2]);
        $this->post(route('cart.add', $bulb), ['quantity' => 3]);

        $response = $this->post(route('checkout.store'), $this->validForm([
            'name'    => '<b>Ali</b> Khan',
            'phone'   => '+92 300-1234567',
            'address' => 'House 1, Street 2, Lahore',
            'comment' => 'Call after 5pm',
        ]));

        $order = Order::with('items')->sole();

        $response->assertRedirect(route('orders.show', $order));
        $response->assertCookie(config('orders.history_cookie'));

        $this->assertSame('UE-' . (100000 + $order->id), $order->reference);
        $this->assertSame(48, strlen($order->public_token));
        $this->assertSame('Ali Khan', $order->customer_name); // HTML stripped
        $this->assertSame('+923001234567', $order->customer_phone);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame(5, $order->items_count);
        $this->assertEquals(2 * 2200 + 3 * 300, $order->subtotal);

        $wireLine = $order->items->firstWhere('product_id', $wire->id);
        $this->assertEquals(2200, $wireLine->unit_price);
        $this->assertSame('W-1', $wireLine->sku);

        $this->assertSame([], Cart::raw());

        Mail::assertQueued(NewOrderRequest::class, fn ($mail) => $mail->hasTo('owner@example.com') && $mail->order->is($order));
        Mail::assertNotQueued(OrderRequestReceived::class); // no customer email given
        Bus::assertDispatched(SendOwnerWhatsAppAlert::class, fn ($job) => $job->order->is($order));
    }

    public function test_customer_gets_confirmation_when_email_given(): void
    {
        $this->post(route('cart.add', $this->makeProduct()));

        $this->post(route('checkout.store'), $this->validForm(['email' => 'ali@example.com']));

        Mail::assertQueued(OrderRequestReceived::class, fn ($mail) => $mail->hasTo('ali@example.com'));
    }

    public function test_empty_cart_cannot_submit(): void
    {
        $this->post(route('checkout.store'), $this->validForm())->assertRedirect(route('cart.index'));

        $this->assertSame(0, Order::count());
    }

    public function test_inactive_products_are_dropped_from_the_order(): void
    {
        $active   = $this->makeProduct(['name' => 'Active']);
        $inactive = $this->makeProduct(['name' => 'Inactive']);

        $this->post(route('cart.add', $active));
        $this->post(route('cart.add', $inactive));
        $inactive->update(['is_active' => false]);

        $this->post(route('checkout.store'), $this->validForm());

        $this->assertSame(['Active'], Order::sole()->items->pluck('product_name')->all());
    }

    public function test_honeypot_creates_nothing(): void
    {
        $this->post(route('cart.add', $this->makeProduct()));

        $this->post(route('checkout.store'), $this->validForm(['website' => 'http://spam.example']))
            ->assertRedirect(route('home'));

        $this->assertSame(0, Order::count());
        Bus::assertNotDispatched(SendOwnerWhatsAppAlert::class);
    }

    public function test_requests_per_phone_are_limited(): void
    {
        $product = $this->makeProduct();

        for ($i = 0; $i < config('orders.limits.per_phone_per_hour'); $i++) {
            $this->post(route('cart.add', $product));
            $this->post(route('checkout.store'), $this->validForm());
        }

        $this->post(route('cart.add', $product));
        $this->post(route('checkout.store'), $this->validForm(['phone' => '03001234567']))
            ->assertSessionHasErrors('form');

        $this->assertSame(config('orders.limits.per_phone_per_hour'), Order::count());
    }

    public function test_order_page_shows_status_privately(): void
    {
        $this->post(route('cart.add', $this->makeProduct(['name' => 'Copper Wire'])));
        $this->post(route('checkout.store'), $this->validForm());
        $order = Order::sole();

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee($order->reference)
            ->assertSee('Copper Wire')
            ->assertSee('0300 ***4567')
            ->assertDontSee('1234567')
            ->assertSee(OrderStatus::New->customerMessage());

        $order->markStatus(OrderStatus::Contacted);
        $this->assertNotNull($order->fresh()->contacted_at);

        $this->get(route('orders.show', $order))->assertSee('check your <strong>WhatsApp</strong>', false);
    }

    public function test_unknown_token_is_404(): void
    {
        $this->get('/orders/' . str_repeat('a', 48))->assertNotFound();
        $this->get('/orders/123')->assertNotFound();
    }

    public function test_my_orders_lists_orders_from_cookie_only(): void
    {
        $this->post(route('cart.add', $this->makeProduct()));
        $this->post(route('checkout.store'), $this->validForm());
        $mine = Order::sole();

        $other = Order::create(['customer_name' => 'Other', 'customer_phone' => '+923111111111']);

        $this->withCookie(config('orders.history_cookie'), json_encode([$mine->public_token]))
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($mine->reference)
            ->assertDontSee($other->reference);

        $this->defaultCookies = []; // a browser without the cookie

        $this->get(route('orders.index'))->assertSee('No order requests found');
    }

    public function test_reorder_puts_items_back_in_cart(): void
    {
        $product = $this->makeProduct();
        $this->post(route('cart.add', $product), ['quantity' => 4]);
        $this->post(route('checkout.store'), $this->validForm());

        $this->post(route('orders.reorder', Order::sole()))->assertRedirect(route('cart.index'));

        $this->assertSame(4, Cart::quantityOf($product));
    }

    public function test_whatsapp_job_sends_alert_once(): void
    {
        $this->post(route('cart.add', $this->makeProduct(['name' => 'Copper Wire'])));
        $this->post(route('checkout.store'), $this->validForm(['comment' => 'Urgent']));
        $order = Order::sole();

        $sent = [];
        $this->app->instance(WhatsAppNotifier::class, new class($sent) implements WhatsAppNotifier {
            public function __construct(public array &$sent) {}
            public function sendToOwner(string $text): void { $this->sent[] = $text; }
        });

        (new SendOwnerWhatsAppAlert($order))->handle(app(WhatsAppNotifier::class));
        (new SendOwnerWhatsAppAlert($order->fresh()))->handle(app(WhatsAppNotifier::class));

        $this->assertCount(1, $sent);
        $this->assertStringContainsString($order->reference, $sent[0]);
        $this->assertStringContainsString('1 x Copper Wire', $sent[0]);
        $this->assertStringContainsString('0300 1234567', $sent[0]);
        $this->assertStringContainsString('Comment: Urgent', $sent[0]);
        $this->assertStringContainsString('https://wa.me/923001234567', $sent[0]);
        $this->assertNotNull($order->fresh()->owner_whatsapp_sent_at);
    }

    public function test_callmebot_driver_calls_api_and_throws_on_error(): void
    {
        Http::fake([
            'api.callmebot.com/*' => Http::sequence()
                ->push('Message queued. You will receive it in a few seconds.')
                ->push('ERROR: APIKey is invalid'),
        ]);

        $driver = new CallMeBotWhatsAppNotifier('+923001234567', 'secret-key');
        $driver->sendToOwner('Hello owner');

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api.callmebot.com/whatsapp.php')
            && $request['phone'] === '+923001234567'
            && $request['apikey'] === 'secret-key'
            && $request['text'] === 'Hello owner');

        $this->expectException(RuntimeException::class);
        $driver->sendToOwner('Hello again');
    }

    public function test_emails_render_with_order_details(): void
    {
        $this->post(route('cart.add', $this->makeProduct(['name' => 'Copper Wire', 'sku' => 'CW-25'])), ['quantity' => 2]);
        $this->post(route('checkout.store'), $this->validForm(['email' => 'ali@example.com', 'comment' => 'Call after 5pm']));
        $order = Order::sole();

        $admin = (new NewOrderRequest($order))->render();
        $this->assertStringContainsString($order->reference, $admin);
        $this->assertStringContainsString('Copper Wire', $admin);
        $this->assertStringContainsString('0300 1234567', $admin);
        $this->assertStringContainsString('https://wa.me/923001234567', $admin);
        $this->assertStringContainsString('Call after 5pm', $admin);

        $customer = (new OrderRequestReceived($order))->render();
        $this->assertStringContainsString(route('orders.show', $order), $customer);
        $this->assertStringContainsString('Cash on Delivery', $customer);
    }

    public function test_driver_is_chosen_from_config(): void
    {
        config(['orders.whatsapp.driver' => 'callmebot']);

        $this->assertInstanceOf(CallMeBotWhatsAppNotifier::class, app(WhatsAppNotifier::class));
    }
}
