<?php

namespace App\Filament\Pages\Website;

use App\Filament\Resources\HomepageCategorySections\HomepageCategorySectionResource;
use App\Filament\Resources\HomepageSliders\HomepageSliderResource;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class HomePage extends ContentPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Home page';

    protected static ?string $title = 'Home page';

    protected static ?int $navigationSort = 3;

    protected static array $groups = ['home'];

    public const BENEFIT_ICONS = [
        'shield'   => 'Shield (genuine)',
        'tag'      => 'Price tag',
        'cash'     => 'Cash',
        'phone'    => 'Phone',
        'truck'    => 'Delivery truck',
        'star'     => 'Star',
        'clock'    => 'Clock',
        'check'    => 'Check mark',
        'bolt'     => 'Lightning bolt',
        'wrench'   => 'Wrench',
        'location' => 'Location',
        'whatsapp' => 'WhatsApp',
    ];

    protected function fields(): array
    {
        return [
            Section::make('Hero')
                ->description(new HtmlString(
                    'The big banner at the top. Slider images are managed in <a class="underline" href="' . HomepageSliderResource::getUrl() . '">Home slider</a>, '
                    . 'and the product rows below in <a class="underline" href="' . HomepageCategorySectionResource::getUrl() . '">Home sections</a>.'
                ))
                ->columns(2)
                ->schema([
                    TextInput::make('home.hero_title')->label('Title')->required()->maxLength(120)->columnSpanFull(),
                    Textarea::make('home.hero_subtitle')->label('Subtitle')->rows(2)->maxLength(300)->columnSpanFull(),
                    TextInput::make('home.hero_button_label')->label('Main button text')->maxLength(30),
                    self::linkInput('home.hero_button_url', 'Main button link'),
                    TextInput::make('home.hero_button2_label')->label('Second button text (optional)')->maxLength(30),
                    self::linkInput('home.hero_button2_url', 'Second button link'),
                    TextInput::make('home.hero_empty_text')
                        ->label('Text in the slider box while there are no slides')
                        ->maxLength(150)
                        ->columnSpanFull(),
                ]),
            Section::make('Benefits strip')
                ->description('The row of icons under the hero (e.g. Genuine products, Cash on delivery).')
                ->schema([
                    Toggle::make('home.benefits_enabled')->label('Show the benefits strip'),
                    Repeater::make('home.benefits')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('icon')->options(self::BENEFIT_ICONS)->required()->native(false),
                            TextInput::make('title')->required()->maxLength(40),
                            TextInput::make('text')->maxLength(80),
                        ])
                        ->columns(3)
                        ->reorderable()
                        ->maxItems(8)
                        ->addActionLabel('Add benefit'),
                ]),
            Section::make('Product blocks')
                ->description(new HtmlString('Rows of products under the hero, in this order: deals, featured, brands, new arrivals, then your '
                    . '<a class="underline" href="' . HomepageCategorySectionResource::getUrl() . '">Home sections</a>. '
                    . 'Featured = products with "Featured on the home page" switched on. Deals = products on sale.'))
                ->columns(2)
                ->schema([
                    Toggle::make('home.deals_enabled')->label('Show deals (on-sale products)'),
                    TextInput::make('home.deals_title')->label('Deals title')->required()->maxLength(40),
                    Toggle::make('home.featured_enabled')->label('Show featured products'),
                    TextInput::make('home.featured_title')->label('Featured title')->required()->maxLength(40),
                    Toggle::make('home.brands_enabled')->label('Show "Shop by brand" strip'),
                    TextInput::make('home.brands_title')->label('Brands title')->required()->maxLength(40),
                    Toggle::make('home.new_enabled')->label('Show new arrivals'),
                    TextInput::make('home.new_title')->label('New arrivals title')->required()->maxLength(40),
                    TextInput::make('home.block_limit')->label('Products per block')->numeric()->integer()->minValue(1)->maxValue(12)->required(),
                    TextInput::make('home.section_view_all')->label('"View all" link text')->required()->maxLength(30),
                ]),
        ];
    }
}
