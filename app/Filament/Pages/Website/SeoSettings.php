<?php

namespace App\Filament\Pages\Website;

use App\Filament\Support\Fields;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class SeoSettings extends ContentPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'SEO';

    protected static ?string $title = 'SEO & sharing';

    protected static ?int $navigationSort = 9;

    protected static array $groups = ['seo'];

    protected function fields(): array
    {
        return [
            Section::make('Google')
                ->description('How the site appears in Google results. Products, categories and content pages also have their own SEO fields.')
                ->schema([
                    TextInput::make('seo.home_title')
                        ->label('Home page title')
                        ->required()
                        ->maxLength(70)
                        ->helperText(self::placeholdersHint() . ' Up to ~60 characters works best.'),
                    TextInput::make('seo.title_suffix')
                        ->label('Added after every other page title')
                        ->maxLength(40)
                        ->helperText('e.g. " – {store}" gives "Shop – Usman Electronics".'),
                    Textarea::make('seo.meta_description')
                        ->label('Default description')
                        ->rows(2)
                        ->required()
                        ->maxLength(160)
                        ->helperText('Used when a page has no description of its own. Up to ~155 characters.'),
                    TextInput::make('seo.google_site_verification')
                        ->label('Google Search Console verification code')
                        ->helperText('Only the code from the "HTML tag" method (the content="…" value).')
                        ->maxLength(100)
                        ->regex('/^[A-Za-z0-9_\-]*$/'),
                ]),
            Section::make('Sharing')
                ->schema([
                    Fields::image('seo.share_image', 'branding', 'Share image')
                        ->helperText('Shown when the site is shared on WhatsApp or Facebook. 1200 × 630 px works best. Falls back to the logo.'),
                ]),
        ];
    }
}
