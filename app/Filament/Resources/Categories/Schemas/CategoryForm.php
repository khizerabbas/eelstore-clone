<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\Fields;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Category')
                    ->columns(2)
                    ->schema([
                        Fields::nameWithSlug(),
                        Fields::slug(),
                        Select::make('parent_id')
                            ->label('Parent category')
                            ->helperText('Optional – makes this a sub-category.')
                            ->relationship(
                                'parent',
                                'name',
                                // A category can't be its own parent
                                fn (Builder $query, ?Category $record) => $record ? $query->whereKeyNot($record->getKey()) : $query,
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('None (top level)'),
                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->helperText('Lower numbers appear first in menus.')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                        Fields::image('image', 'categories', 'Category image')->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Visible on the website')
                            ->default(true),
                    ]),
                Fields::seoSection(),
            ]);
    }
}
