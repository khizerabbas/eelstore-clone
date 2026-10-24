<?php

namespace App\Filament\Pages\Website;

use App\Filament\Resources\Pages\PageResource;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class FooterContent extends ContentPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Footer';

    protected static ?string $title = 'Footer';

    protected static ?int $navigationSort = 6;

    protected static array $groups = ['footer'];

    protected function fields(): array
    {
        return [
            Section::make('About')
                ->schema([
                    Textarea::make('footer.about_text')
                        ->label('Short text under the logo')
                        ->rows(3)
                        ->maxLength(400)
                        ->helperText(self::placeholdersHint()),
                ]),
            Section::make('Menu column')
                ->schema([
                    TextInput::make('footer.menu_heading')->label('Heading')->required()->maxLength(40),
                    Repeater::make('footer.menu_links')
                        ->label('Links')
                        ->schema([
                            TextInput::make('label')->required()->maxLength(40),
                            self::linkInput('url')->required(),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->maxItems(10)
                        ->addActionLabel('Add link'),
                ]),
            Section::make('Other columns')
                ->description(new HtmlString(
                    'Categories are listed automatically (top-level, in your category order). '
                    . 'The information column lists <a class="underline" href="' . PageResource::getUrl() . '">Content pages</a> marked "Link in the footer". '
                    . 'Contact details and social links come from Store & contact.'
                ))
                ->columns(2)
                ->schema([
                    TextInput::make('footer.categories_heading')->label('Categories heading')->required()->maxLength(40),
                    TextInput::make('footer.categories_limit')->label('How many categories')->numeric()->integer()->minValue(0)->maxValue(20)->required(),
                    TextInput::make('footer.pages_heading')->label('Information heading')->required()->maxLength(40),
                    TextInput::make('footer.contact_heading')->label('Contact heading')->required()->maxLength(40),
                ]),
            Section::make('Bottom line')
                ->columns(2)
                ->schema([
                    TextInput::make('footer.copyright')
                        ->label('Copyright')
                        ->maxLength(150)
                        ->helperText(self::placeholdersHint()),
                    TextInput::make('footer.bottom_text')
                        ->label('Right side text (optional)')
                        ->maxLength(150),
                ]),
        ];
    }
}
