<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Support\Fields;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(['default' => 3, 'lg' => 2])
                    ->schema([
                        Section::make('Page')
                            ->schema([
                                Fields::nameWithSlug('title', 'Title'),
                                Fields::slug(),
                                RichEditor::make('body')
                                    ->label('Content')
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'underline', 'link'],
                                        ['h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'table'],
                                        ['undo', 'redo'],
                                    ]),
                            ]),
                        Fields::seoSection(),
                    ]),
                Section::make('Publishing')
                    ->columnSpan(['default' => 3, 'lg' => 1])
                    ->schema([
                        Toggle::make('is_published')
                            ->label('Published')
                            ->default(true),
                        Toggle::make('show_in_footer')
                            ->label('Link in the footer')
                            ->default(true),
                        TextInput::make('sort_order')
                            ->label('Footer order')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                    ]),
            ]);
    }
}
