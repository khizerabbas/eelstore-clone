<?php

namespace Tests\Feature;

use App\Filament\Resources\ErrorLogs\ErrorLogResource;
use App\Filament\Resources\ErrorLogs\Pages\ManageErrorLogs;
use App\Jobs\SendOwnerWhatsAppAlert;
use App\Models\Category;
use App\Models\ErrorLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppNotifier;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ErrorLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Route::middleware('web')->group(function () {
            Route::post('/_test/boom', fn () => throw new RuntimeException('Checkout exploded'));
            Route::get('/_test/expired', fn () => abort(419));
        });
    }

    public function test_server_errors_are_saved_and_grouped(): void
    {
        $this->post('/_test/boom', ['name' => 'Ali', 'phone' => '03001234567', 'password' => 'secret-value'])->assertStatus(500);
        $this->post('/_test/boom', ['name' => 'Ali'])->assertStatus(500);

        $log = ErrorLog::sole();
        $this->assertSame('server', $log->source);
        $this->assertSame('Checkout exploded', $log->message);
        $this->assertSame(RuntimeException::class, $log->type);
        $this->assertSame(2, $log->occurrences);
        $this->assertStringEndsWith('/_test/boom', $log->url);
        $this->assertSame('POST', $log->method);
        $this->assertNotEmpty($log->trace);
        $this->assertStringStartsWith('tests/Feature/ErrorLogTest.php', $log->file);
    }

    public function test_secrets_are_never_stored(): void
    {
        $this->post('/_test/boom', ['name' => 'Ali', 'password' => 'secret-value', '_token' => 'abc']);

        $this->assertArrayNotHasKey('password', ErrorLog::sole()->context['input'] ?? []);
        $this->assertStringNotContainsString('secret-value', json_encode(ErrorLog::sole()->toArray()));
    }

    public function test_expired_pages_and_rate_limits_are_saved_but_not_404s(): void
    {
        $this->get('/_test/expired')->assertStatus(419);
        $this->get('/no-such-page')->assertNotFound();

        $category = Category::create(['name' => 'W', 'slug' => 'w']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Wire', 'slug' => 'wire', 'price' => 10]);
        for ($i = 0; $i < 61; $i++) {
            $this->post(route('cart.add', $product));
        }

        $this->assertSame(['HTTP 419', 'HTTP 429'], ErrorLog::orderBy('type')->pluck('type')->all());
        $this->assertSame('warning', ErrorLog::where('type', 'HTTP 419')->value('level'));
    }

    public function test_browser_reports_are_saved(): void
    {
        $this->postJson(route('client-errors.store'), [
            'type'    => 'Network error',
            'message' => 'cart request could not reach the server: Failed to fetch',
            'page'    => 'https://example.pk/shop',
            'details' => ['action' => '/cart/add/1'],
        ])->assertNoContent();

        $log = ErrorLog::sole();
        $this->assertSame('browser', $log->source);
        $this->assertSame('https://example.pk/shop', $log->url);
        $this->assertSame('/cart/add/1', $log->context['details']['action']);

        // Noise from extensions/cross-origin scripts is ignored; junk is rejected
        $this->postJson(route('client-errors.store'), ['message' => 'Script error.'])->assertNoContent();
        $this->postJson(route('client-errors.store'), [])->assertStatus(422);
        $this->assertSame(1, ErrorLog::count());
    }

    public function test_failed_background_jobs_are_saved_with_the_order(): void
    {
        $order = Order::create(['customer_name' => 'A', 'customer_phone' => '+923001234567']);
        $this->app->instance(WhatsAppNotifier::class, new class implements WhatsAppNotifier {
            public function sendToOwner(string $text): void
            {
                throw new RuntimeException('CallMeBot is down');
            }
        });

        try {
            SendOwnerWhatsAppAlert::dispatch($order); // sync queue in tests
        } catch (RuntimeException) {
        }

        $log = ErrorLog::where('source', 'queue')->sole();
        $this->assertSame('CallMeBot is down', $log->message);
        $this->assertSame($order->reference, $log->context['order']);
        $this->assertSame(SendOwnerWhatsAppAlert::class, $log->context['job']);
    }

    public function test_resolved_errors_reopen_when_they_happen_again(): void
    {
        $this->post('/_test/boom');
        ErrorLog::query()->update(['resolved_at' => now()]);

        $this->post('/_test/boom');

        $this->assertNull(ErrorLog::sole()->resolved_at);
    }

    public function test_only_owner_sees_the_error_log(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(ErrorLogResource::getUrl())->assertForbidden();

        $this->actingAs(User::factory()->owner()->create());
        $this->get(ErrorLogResource::getUrl())->assertOk();
    }

    public function test_owner_can_resolve_and_clear(): void
    {
        $this->post('/_test/boom');
        $this->get('/_test/expired');
        $this->actingAs(User::factory()->owner()->create());

        $error = ErrorLog::where('source', 'server')->sole();
        Livewire::test(ManageErrorLogs::class)->callTableAction('resolve', $error);
        $this->assertNotNull($error->fresh()->resolved_at);

        Livewire::test(ManageErrorLogs::class)->callAction('clearResolved');
        $this->assertSame(1, ErrorLog::count());

        Livewire::test(ManageErrorLogs::class)->callAction('clearAll');
        $this->assertSame(0, ErrorLog::count());
    }

    public function test_old_errors_are_pruned(): void
    {
        $this->post('/_test/boom');
        ErrorLog::query()->update(['last_seen_at' => now()->subDays(31)]);
        $this->get('/_test/expired');

        $this->artisan('model:prune', ['--model' => [ErrorLog::class]])->assertSuccessful();

        $this->assertSame(['HTTP 419'], ErrorLog::pluck('type')->all());
    }
}
