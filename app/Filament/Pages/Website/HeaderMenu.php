<?php

namespace App\Filament\Pages\Website;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class HeaderMenu extends ContentPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $navigationLabel = 'Header & menu';

    protected static ?string $title = 'Header & menu';

    protected static ?int $navigationSort = 2;

    protected static array $groups = ['header'];

    protected function fields(): array
    {
        $label = fn (string $name, string $text) => TextInput::make("header.{$name}")->label($text)->required()->maxLength(40);

        return [
            Section::make('Top bar')
                ->description('The thin bar above the header. The phone number comes from Store & contact.')
                ->columns(2)
                ->schema([
                    Toggle::make('header.topbar_enabled')->label('Show the top bar')->columnSpanFull(),
                    TextInput::make('header.topbar_message')
                        ->label('Announcement (left side)')
                        ->placeholder('e.g. Free delivery in Lahore on orders over Rs 10,000')
                        ->maxLength(150),
                    TextInput::make('header.topbar_phone_label')->label('Text before the phone number')->maxLength(40),
                ]),
            Section::make('Search')
                ->columns(2)
                ->schema([
                    TextInput::make('header.search_placeholder')->label('Search box placeholder')->required()->maxLength(60),
                    TextInput::make('header.search_all_label')->label('"All categories" option')->required()->maxLength(40),
                ]),
            Section::make('Menu labels')
                ->columns(3)
                ->schema([
                    $label('nav_home', 'Home'),
                    $label('nav_categories', 'Categories'),
                    $label('nav_shop', 'Shop'),
                    $label('nav_contact', 'Contact'),
                    $label('nav_my_orders', 'My Orders'),
                    $label('nav_wishlist', 'Wishlist'),
                    $label('nav_cart', 'Cart'),
                    Toggle::make('header.show_my_orders')->label('Show "My Orders"'),
                    Toggle::make('header.show_wishlist')->label('Show "Wishlist"'),
                ]),
            Section::make('Extra menu links')
                ->description('Added to the main menu after "Shop" – e.g. a content page (/pages/bulk-orders) or a category (/category/lighting).')
                ->schema([
                    Repeater::make('header.extra_links')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('label')->required()->maxLength(30),
                            self::linkInput('url')->required(),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->maxItems(6)
                        ->defaultItems(0)
                        ->addActionLabel('Add menu link'),
                ]),
        ];
    }
}
