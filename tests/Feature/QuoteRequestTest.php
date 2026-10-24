<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteRequests\Pages\ViewQuoteRequest;
use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use App\Jobs\SendQuoteWhatsAppAlert;
use App\Mail\NewQuoteRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        config(['orders.admin_email' => 'owner@example.com']);
        Mail::fake();
        Bus::fake([SendQuoteWhatsAppAlert::class]);
        Storage::fake('local');
        Storage::fake('public');
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Bilal Electric',
            'phone'         => '0321 7654321',
            'business_name' => 'Bilal Electric Works',
            'city'          => 'Johar Town',
            'items'         => "7/29 wire – 5 coils\nLED bulb 12W – 50 pcs",
        ], $overrides);
    }

    public function test_page_loads_and_is_linked_from_cart_and_footer(): void
    {
        $this->get('/bulk-quote')->assertOk()->assertSee('Bulk Orders &amp; Quotes', false);
        $this->get('/cart')->assertSee(route('quotes.create'));
        $this->get('/')->assertSee(url('/bulk-quote'));
    }

    public function test_quote_request_is_saved_and_owner_notified(): void
    {
        $this->post('/bulk-quote', $this->form())->assertRedirect(route('quotes.thanks'));

        $quote = QuoteRequest::sole();
        $this->assertSame('QR-' . (100000 + $quote->id), $quote->reference);
        $this->assertSame('+923217654321', $quote->phone);
        $this->assertSame(QuoteStatus::New, $quote->status);
        $this->assertStringContainsString('LED bulb 12W', $quote->items);

        Mail::assertQueued(NewQuoteRequest::class, fn ($mail) => $mail->hasTo('owner@example.com'));
        Bus::assertDispatched(SendQuoteWhatsAppAlert::class);

        $this->followRedirects($this->post('/bulk-quote', $this->form(['phone' => '03001112222'])))
            ->assertSee('Thank you! Your quote request was sent.')
            ->assertSee('QR-');
    }

    public function test_items_or_a_file_are_required_and_phone_is_validated(): void
    {
        $this->post('/bulk-quote', $this->form(['items' => '', 'phone' => '123']))
            ->assertSessionHasErrors(['items', 'phone']);

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_list_file_is_stored_privately_and_downloadable_by_admins_only(): void
    {
        $this->post('/bulk-quote', $this->form([
            'items'      => '',
            'attachment' => UploadedFile::fake()->image('my list.jpg', 800, 1200),
        ]))->assertRedirect(route('quotes.thanks'));

        $quote = QuoteRequest::sole();
        $this->assertStringStartsWith('quotes/', $quote->attachment_path);
        $this->assertSame('my list.jpg', $quote->attachment_name);
        Storage::disk('local')->assertExists($quote->attachment_path);
        $this->assertSame([], Storage::disk('public')->allFiles()); // never on the public disk

        // Attached to the admin email
        Mail::assertQueued(NewQuoteRequest::class, fn ($mail) => count($mail->attachments()) === 1);

        $url = route('admin.quotes.attachment', $quote->id);
        $this->get($url)->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->create());
        $this->get($url)->assertOk()->assertDownload('my list.jpg');

        // Deleting the request deletes the file
        $path = $quote->attachment_path;
        $quote->delete();
        Storage::disk('local')->assertMissing($path);
    }

    public function test_dangerous_files_are_rejected(): void
    {
        // A PHP script renamed to .jpg – a real upload, so the type is detected from the content (fake uploads use the name)
        $path = tempnam(sys_get_temp_dir(), 'quote');
        file_put_contents($path, '<?php echo "hacked"; ?>');
        $disguised = new UploadedFile($path, 'list.jpg', null, null, true);

        $this->post('/bulk-quote', $this->form(['attachment' => $disguised]))->assertSessionHasErrors('attachment');
        $this->post('/bulk-quote', $this->form(['attachment' => UploadedFile::fake()->create('big.pdf', 11 * 1024, 'application/pdf')]))
            ->assertSessionHasErrors('attachment');

        $this->assertSame(0, QuoteRequest::count());
    }

    public function test_cart_items_can_be_included(): void
    {
        $category = Category::create(['name' => 'W', 'slug' => 'w']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Copper Wire', 'slug' => 'copper', 'price' => 2500]);
        $this->post(route('cart.add', $product), ['quantity' => 4]);

        $this->get('/bulk-quote')->assertSee('Also include the items in my cart');

        $this->post('/bulk-quote', $this->form(['include_cart' => 1]));

        $quote = QuoteRequest::sole();
        $this->assertSame('Copper Wire', $quote->cart_items[0]['product_name']);
        $this->assertSame(4, $quote->cart_items[0]['quantity']);
        $this->assertStringContainsString('From cart: 4 x Copper Wire', SendQuoteWhatsAppAlert::message($quote));
    }

    public function test_honeypot_and_rate_limit(): void
    {
        $this->post('/bulk-quote', $this->form(['website' => 'spam']))->assertRedirect(route('quotes.thanks'));
        $this->assertSame(0, QuoteRequest::count());

        for ($i = 0; $i < 3; $i++) {
            $this->post('/bulk-quote', $this->form());
        }
        $this->post('/bulk-quote', $this->form())->assertSessionHasErrors('form');
        $this->assertSame(3, QuoteRequest::count());
    }

    public function test_admin_can_view_and_change_status(): void
    {
        $this->post('/bulk-quote', $this->form());
        $quote = QuoteRequest::sole();
        $this->actingAs(User::factory()->create(['name' => 'Usman']));

        $this->get(QuoteRequestResource::getUrl('index'))->assertOk()->assertSee($quote->reference);
        $this->get(QuoteRequestResource::getUrl('view', ['record' => $quote]))->assertOk()->assertSee('Bilal Electric Works');

        Livewire::test(ViewQuoteRequest::class, ['record' => $quote->id])
            ->callAction('changeStatus', data: ['status' => QuoteStatus::Quoted->value, 'note' => 'Sent prices on WhatsApp']);

        $quote->refresh();
        $this->assertSame(QuoteStatus::Quoted, $quote->status);
        $this->assertStringContainsString('Sent prices on WhatsApp', $quote->admin_note);
        $this->assertFalse(auth()->user()->can('create', QuoteRequest::class));
    }

    public function test_whatsapp_message_contents(): void
    {
        $this->post('/bulk-quote', $this->form());

        $message = SendQuoteWhatsAppAlert::message(QuoteRequest::sole());

        $this->assertStringContainsString('New bulk quote request QR-', $message);
        $this->assertStringContainsString('Business: Bilal Electric Works', $message);
        $this->assertStringContainsString('7/29 wire – 5 coils; LED bulb 12W – 50 pcs', $message);
        $this->assertStringContainsString('https://wa.me/923217654321', $message);
    }
}
