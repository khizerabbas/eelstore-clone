<?php

namespace App\Filament\Resources\HomepageSliders;

use App\Filament\Resources\HomepageSliders\Pages\ManageHomepageSliders;
use App\Filament\Support\Fields;
use App\Models\HomepageSlider;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class HomepageSliderResource extends Resource
{
    protected static ?string $model = HomepageSlider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Storefront';

    protected static ?string $navigationLabel = 'Home slider';

    protected static ?string $modelLabel = 'slide';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Fields::image('image_path', 'sliders', 'Slide image')
                    ->required()
                    ->helperText('Wide image, about 1600 × 600 px. JPG, PNG or WebP, up to 10 MB. Only the first 3 visible slides are shown.'),
                TextInput::make('title')
                    ->label('Alt text / title')
                    ->helperText('Describes the image for Google and screen readers.')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Visible')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Slide')
                    ->state(fn (HomepageSlider $record) => $record->imageUrl())
                    ->imageHeight(56),
                TextColumn::make('title')
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label('Visible'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHomepageSliders::route('/'),
        ];
    }
}
