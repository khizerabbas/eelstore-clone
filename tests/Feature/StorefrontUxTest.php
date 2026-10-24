<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StorefrontUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
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

    public function test_contact_form_accepts_a_phone_without_an_email(): void
    {
        $this->post(route('contact.submit'), [
            'name'    => 'Ali Khan',
            'phone'   => '0300 1234567',
            'message' => 'Do you have 7/29 wire in stock?',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas(ContactMessage::class, ['name' => 'Ali Khan', 'email' => null, 'phone' => '0300 1234567']);
    }

    public function test_contact_form_needs_a_phone_or_an_email(): void
    {
        $this->post(route('contact.submit'), [
            'name'    => 'Ali Khan',
            'message' => 'Do you have 7/29 wire in stock?',
        ])->assertSessionHasErrors(['phone', 'email']);

        $this->assertDatabaseCount(ContactMessage::class, 0);
    }

    public function test_invalid_fields_are_marked_for_screen_readers(): void
    {
        $this->from(route('contact.show'))
            ->followingRedirects()
            ->post(route('contact.submit'), ['name' => '', 'phone' => '0300 1234567', 'message' => 'Hello there, a question'])
            ->assertSee('aria-invalid="true" aria-describedby="name-error"', false)
            ->assertSee('id="name-error"', false)
            ->assertSee('Please check the highlighted field below.');
    }

    public function test_missing_pages_use_the_store_layout(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('role="search"', false);

        $this->get('/product/no-such-product')->assertNotFound()->assertSee('Page not found');
    }

    public function test_server_error_page_does_not_need_the_database(): void
    {
        $html = view('errors.500')->render();

        $this->assertStringContainsString('Something went wrong', $html);
        $this->assertStringNotContainsString('@vite', $html);
    }

    public function test_cart_shows_quantity_steppers_and_rupee_totals(): void
    {
        $product = $this->makeProduct(['price' => 1250]);

        $this->post(route('cart.add', $product), ['quantity' => 2]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Decrease quantity of Test Wire')
            ->assertSee('Increase quantity of Test Wire')
            ->assertSee('Rs 2,500')
            ->assertDontSee('2,500.00');
    }

    public function test_on_sale_filter_only_lists_real_discounts(): void
    {
        $this->makeProduct(['name' => 'Real Deal', 'is_on_sale' => true, 'sale_price' => 800]);
        $this->makeProduct(['name' => 'Ticked No Price', 'is_on_sale' => true, 'sale_price' => null]);
        $this->makeProduct(['name' => 'Sale Above Price', 'is_on_sale' => true, 'sale_price' => 1200]);

        $this->get('/shop?on_sale=1')
            ->assertOk()
            ->assertSee('Real Deal')
            ->assertDontSee('Ticked No Price')
            ->assertDontSee('Sale Above Price');
    }

    public function test_page_number_past_the_end_goes_to_the_last_page(): void
    {
        $this->makeProduct();

        $this->get('/shop?sort=latest&page=9')->assertRedirect(url('/shop') . '?page=1&sort=latest');
        $this->get('/category/wires?page=9')->assertRedirect(url('/category/wires') . '?page=1');
    }

    public function test_cart_minus_button_at_one_removes_the_line(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.add', $product));
        $this->post(route('cart.update', $product), ['quantity' => 0]);

        $this->get(route('cart.index'))->assertDontSee('Decrease quantity of Test Wire');
    }
}
