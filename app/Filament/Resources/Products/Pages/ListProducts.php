<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CSV import (Excel: File → Save as → CSV). Download the example file from the import window.
            ImportAction::make()
                ->label('Import')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->importer(ProductImporter::class)
                ->maxRows(5000)
                ->visible(fn () => auth()->user()?->can('create', \App\Models\Product::class)),
            // CSV or Excel (.xlsx) export – same columns as the import, so it can be edited and imported back
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->exporter(ProductExporter::class),
            CreateAction::make(),
        ];
    }
}
