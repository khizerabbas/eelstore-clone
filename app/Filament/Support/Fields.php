<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

// Field presets shared by the admin resources, so they all behave (and validate) the same way
class Fields
{
    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp']; // no SVG (can carry scripts)

    public const IMAGE_MAX_KB = 10240; // 10 MB – HD photos; the storefront should serve resized copies

    // Name input that fills the slug while creating (or while the slug is still empty)
    public static function nameWithSlug(string $name = 'name', string $label = 'Name'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->required()
            ->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (string $operation, ?string $state, Set $set, Get $get) {
                if ($operation === 'create' || blank($get('slug'))) {
                    $set('slug', Str::slug(str_replace(['/', '\\'], ' ', (string) $state))); // "7/29 wire" → 7-29-wire
                }
            });
    }

    public static function slug(): TextInput
    {
        return TextInput::make('slug')
            ->label('URL slug')
            ->helperText('Used in the page address. Lowercase letters, numbers and dashes.')
            ->required()
            ->maxLength(255)
            ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->unique(ignoreRecord: true)
            ->dehydrateStateUsing(fn (?string $state) => Str::slug((string) $state));
    }

    public static function image(string $name, string $directory, string $label = 'Image'): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes(self::IMAGE_TYPES)
            ->maxSize(self::IMAGE_MAX_KB)
            ->imageEditor()
            ->helperText('JPG, PNG or WebP, up to 10 MB.');
    }

    public static function seoSection(): Section
    {
        return Section::make('SEO')
            ->description('Optional. Leave empty to use the name and description automatically.')
            ->collapsed()
            ->schema([
                TextInput::make('meta_title')
                    ->label('Meta title')
                    ->maxLength(70)
                    ->helperText('Shown as the title in Google results. Up to ~60 characters works best.'),
                Textarea::make('meta_description')
                    ->label('Meta description')
                    ->rows(2)
                    ->maxLength(160)
                    ->helperText('Shown under the title in Google results. Up to ~155 characters.'),
            ]);
    }

    public static function rupees($state): string
    {
        return $state === null || $state === '' ? '—' : 'Rs ' . number_format((float) $state);
    }
}
