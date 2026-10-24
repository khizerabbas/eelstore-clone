<?php

namespace App\Filament\Resources\QuoteRequests\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

// Only the internal note is editable – everything else is what the customer sent
class QuoteRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Internal')
                    ->schema([
                        Textarea::make('admin_note')
                            ->label('Admin note (prices quoted, follow-up date …) – never shown to the customer')
                            ->rows(8)
                            ->maxLength(5000),
                    ]),
            ]);
    }
}
