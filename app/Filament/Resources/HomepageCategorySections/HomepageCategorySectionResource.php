<?php

namespace App\Filament\Resources\HomepageCategorySections;

use App\Filament\Resources\HomepageCategorySections\Pages\ManageHomepageCategorySections;
use App\Models\HomepageCategorySection;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

// Product rows on the home page: each shows the 8 latest products of one category
class HomepageCategorySectionResource extends Resource
{
    protected static ?string $model = HomepageCategorySection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Home sections';

    protected static ?string $modelLabel = 'home section';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('category_id')
                    ->label('Category')
                    ->helperText('The section shows the 8 newest visible products from this category.')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Toggle::make('is_active')
                    ->label('Visible')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('position')
                    ->label('#'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('Category deleted – pick another'),
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
            'index' => ManageHomepageCategorySections::route('/'),
        ];
    }
}
