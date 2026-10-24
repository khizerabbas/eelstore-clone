<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Support\Fields;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(['default' => 3, 'lg' => 2])
                    ->schema([
                        Section::make('Product')
                            ->columns(2)
                            ->schema([
                                Fields::nameWithSlug()->columnSpanFull(),
                                Fields::slug(),
                                TextInput::make('sku')
                                    ->label('SKU / code')
                                    ->maxLength(100),
                                Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('company_id')
                                    ->label('Brand')
                                    ->relationship('company', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('No brand'),
                                Textarea::make('short_description')
                                    ->label('Short description')
                                    ->helperText('One or two lines shown near the price.')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                RichEditor::make('description')
                                    ->label('Full description')
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'underline', 'link'],
                                        ['h2', 'h3', 'bulletList', 'orderedList', 'table'],
                                        ['undo', 'redo'],
                                    ])
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Images')
                            ->schema([
                                Fields::image('image', 'products', 'Main image'),
                                Fields::image('gallery', 'products/gallery', 'More images')
                                    ->multiple()
                                    ->reorderable()
                                    ->maxFiles(8)
                                    ->helperText('Up to 8 extra images (JPG, PNG or WebP, up to 10 MB each). Drag to reorder.'),
                            ]),
                        Fields::seoSection(),
                    ]),
                Grid::make(1)
                    ->columnSpan(['default' => 3, 'lg' => 1])
                    ->schema([
                        Section::make('Visibility')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Visible on the website')
                                    ->default(true),
                                Toggle::make('is_featured')
                                    ->label('Featured on the home page'),
                            ]),
                        Section::make('Price')
                            ->schema([
                                TextInput::make('price')
                                    ->label('Regular price')
                                    ->prefix('Rs')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(99999999)
                                    ->required(),
                                Toggle::make('is_on_sale')
                                    ->label('On sale')
                                    ->live(),
                                TextInput::make('sale_price')
                                    ->label('Sale price')
                                    ->prefix('Rs')
                                    ->numeric()
                                    ->minValue(0)
                                    ->lt('price')
                                    ->required(fn (Get $get) => (bool) $get('is_on_sale'))
                                    ->visible(fn (Get $get) => (bool) $get('is_on_sale'))
                                    ->helperText('Customers pay this price while "On sale" is on.'),
                            ]),
                        Section::make('Stock')
                            ->schema([
                                TextInput::make('stock')
                                    ->label('Units in stock')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('For your reference – orders are confirmed by phone, so they are not blocked at 0.'),
                            ]),
                    ]),
            ]);
    }
}
