<?php

namespace App\Filament\Pages\Website;

use App\Filament\Support\Fields;
use App\Support\Theme;
use BackedEnum;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class BrandingTheme extends ContentPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $navigationLabel = 'Branding & theme';

    protected static ?string $title = 'Branding & theme';

    protected static ?int $navigationSort = 1;

    protected static array $groups = ['branding'];

    protected function fields(): array
    {
        return [
            Section::make('Logo')
                ->description('Shown in the header, footer and admin panel. The store name itself is set in Store & contact.')
                ->columns(2)
                ->schema([
                    Fields::image('branding.logo', 'branding', 'Logo image')
                        ->helperText('Optional. A wide PNG with a transparent background looks best (about 400 × 120 px). Up to 10 MB.'),
                    Fields::image('branding.favicon', 'branding', 'Browser tab icon (favicon)')
                        ->helperText('Square PNG, at least 64 × 64 px.'),
                    TextInput::make('branding.logo_text')
                        ->label('Logo letters (used when there is no logo image)')
                        ->maxLength(3)
                        ->required(),
                    TextInput::make('branding.tagline')
                        ->label('Tagline')
                        ->maxLength(80),
                    Toggle::make('branding.show_name')
                        ->label('Show the store name next to the logo'),
                    Fields::image('branding.placeholder_image', 'branding', 'Placeholder image (optional)')
                        ->helperText('Shown wherever a product or slide has no image, or an image fails to load. Leave empty to use the built-in "No preview available" image.')
                        ->columnSpanFull(),
                ]),
            Section::make('Colours')
                ->description('Changes apply to the whole website instantly.')
                ->columns(3)
                ->schema([
                    ColorPicker::make('branding.brand_color')
                        ->label('Brand colour')
                        ->helperText('Header, footer and dark buttons. Must be dark enough for white text.')
                        ->required()
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->rule(self::contrastRule('#ffffff', 4.5, 'This colour is too light – white text on it would be hard to read. Pick a darker shade.')),
                    ColorPicker::make('branding.accent_color')
                        ->label('Accent colour')
                        ->helperText('Main buttons, badges and highlights. Text colour on it is picked automatically.')
                        ->required()
                        ->regex('/^#[0-9a-fA-F]{6}$/'),
                    ColorPicker::make('branding.page_background')
                        ->label('Page background')
                        ->helperText('Behind the page content. Keep it light.')
                        ->required()
                        ->regex('/^#[0-9a-fA-F]{6}$/')
                        ->rule(self::contrastRule('#111827', 10, 'This background is too dark for the page text. Pick a lighter shade.')),
                ]),
        ];
    }

    private static function contrastRule(string $against, float $min, string $message): Closure
    {
        return fn () => function (string $attribute, mixed $value, Closure $fail) use ($against, $min, $message) {
            if (is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) && Theme::contrast($value, $against) < $min) {
                $fail($message);
            }
        };
    }
}
