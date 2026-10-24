<?php

namespace App\Filament\Pages\Website;

use App\Enums\OrderStatus;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

// Wording used across the storefront pages, plus the order-status messages customers see
class PageTexts extends ContentPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $navigationLabel = 'Page texts';

    protected static ?string $title = 'Page texts';

    protected static ?int $navigationSort = 7;

    protected static array $groups = ['pages', 'order_status'];

    protected function fields(): array
    {
        $text = fn (string $key, string $label, int $max = 150) => TextInput::make("pages.{$key}")->label($label)->required()->maxLength($max);
        $long = fn (string $key, string $label, int $max = 400) => Textarea::make("pages.{$key}")->label($label)->rows(2)->required()->maxLength($max);

        return [
            Tabs::make('Texts')
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Shop & products')->columns(2)->schema([
                        $text('shop_title', 'Shop page title', 40),
                        $long('shop_intro', 'Shop page intro'),
                        $text('shop_empty', 'No products (filters)'),
                        $text('search_empty', 'No search results'),
                        $text('category_empty', 'Empty category'),
                        $text('sale_badge', 'Sale badge', 15),
                        $text('add_to_cart', '"Add to cart" button', 30),
                        $text('product_no_description', 'Product without a short description')->helperText(self::placeholdersHint()),
                    ]),
                    Tab::make('Cart & wishlist')->columns(2)->schema([
                        $text('cart_title', 'Cart page title', 40),
                        $text('cart_empty', 'Empty cart'),
                        $text('cart_checkout_button', 'Checkout button', 40),
                        $long('cart_cod_note', 'Note under the checkout button'),
                        $text('wishlist_title', 'Wishlist page title', 40),
                        $text('wishlist_empty', 'Empty wishlist'),
                    ]),
                    Tab::make('Checkout')->columns(2)->schema([
                        $text('checkout_title', 'Checkout page title', 40),
                        $long('checkout_intro', 'Intro under the title'),
                        $text('checkout_submit', 'Submit button', 40),
                        $long('checkout_consent', 'Small print under the button'),
                        $text('delivery_note', 'Delivery note in the summary'),
                        $text('checkout_steps_title', '"How it works" heading', 40),
                        Repeater::make('pages.checkout_steps')
                            ->label('"How it works" steps')
                            ->schema([TextInput::make('text')->hiddenLabel()->required()->maxLength(150)])
                            ->reorderable()
                            ->maxItems(6)
                            ->columnSpanFull(),
                    ]),
                    Tab::make('Thank you & My Orders')->columns(2)->schema([
                        $text('thankyou_title', 'Thank-you heading')->helperText('Use {name} for the customer\'s name.'),
                        $long('thankyou_text', 'Thank-you text')->helperText('Use {name}, {reference} (order number) and {phone} (masked).'),
                        $text('thankyou_whatsapp_button', 'WhatsApp button on the thank-you page', 80),
                        $text('my_orders_title', 'My Orders page title', 40),
                        $text('my_orders_intro', 'My Orders intro'),
                        $text('my_orders_empty', 'No orders in this browser'),
                        $long('my_orders_note', 'Note under the list'),
                    ]),
                    Tab::make('Order status messages')->schema(
                        collect(OrderStatus::cases())->map(fn (OrderStatus $status) => Textarea::make('order_status.' . $status->value)
                            ->label($status->getLabel())
                            ->helperText('Shown on the customer\'s order page while the order is "' . $status->getLabel() . '".')
                            ->rows(2)
                            ->required()
                            ->maxLength(300))->all()
                    ),
                    Tab::make('Bulk quotes')->columns(2)->schema([
                        $text('quote_title', 'Page title', 60),
                        $long('quote_intro', 'Intro'),
                        $text('quote_items_label', 'Items box label', 80),
                        Textarea::make('pages.quote_items_placeholder')->label('Items box example text')->rows(4)->maxLength(400),
                        $text('quote_attachment_label', 'File upload label'),
                        $text('quote_include_cart', '"Include my cart" checkbox'),
                        $text('quote_submit', 'Submit button', 40),
                        $text('quote_thanks_title', 'Thank-you heading'),
                        $long('quote_thanks_text', 'Thank-you text'),
                        $text('cart_quote_link', 'Link on the cart page'),
                    ]),
                    Tab::make('Contact')->columns(2)->schema([
                        $text('contact_title', 'Contact page title', 40),
                        $long('contact_intro', 'Contact page intro')->helperText(self::placeholdersHint()),
                        $text('contact_phone_note', 'Note under the phone (used when no opening hours are set)'),
                        $text('contact_email_note', 'Note under the email'),
                        $text('contact_button', 'Send button', 30),
                    ]),
                ]),
        ];
    }
}
