<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Filament\Support\Fields;
use App\Models\Company;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

// "Companies" in the database, shown as Brands in the admin
class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'brand';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Fields::nameWithSlug(),
                Fields::slug(),
                Fields::image('logo', 'brands', 'Logo')->columnSpanFull(),
                Textarea::make('description')
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Visible on the website')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount('products'))
            ->columns([
                ImageColumn::make('logo')
                    ->defaultImageUrl(fn () => \App\Support\Content::placeholderUrl())
                    ->label('')
                    ->disk('public')
                    ->imageHeight(32),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Visible'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('view_on_site')
                    ->label('')
                    ->tooltip('View on website')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Company $record) => route('brand.show', $record->slug), shouldOpenInNewTab: true)
                    ->visible(fn (Company $record) => $record->is_active),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Products of this brand are kept, they just lose the brand.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCompanies::route('/'),
        ];
    }
}
