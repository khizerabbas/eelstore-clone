<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Products → CSV/Excel export (Catalog → Products → Export). The headers match the importer,
 * so an exported file can be edited in Excel (e.g. prices) and imported back.
 */
class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        $yesNo = fn (string $name, string $label) => ExportColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn ($state) => $state ? 'yes' : 'no');

        return [
            ExportColumn::make('name')->label('Name'),
            ExportColumn::make('sku')->label('SKU'),
            ExportColumn::make('category.name')->label('Category'),
            ExportColumn::make('company.name')->label('Brand'),
            ExportColumn::make('price')->label('Price (Rs)'),
            ExportColumn::make('sale_price')->label('Sale price (Rs)'),
            $yesNo('is_on_sale', 'On sale'),
            ExportColumn::make('stock')->label('Stock'),
            $yesNo('is_active', 'Visible'),
            $yesNo('is_featured', 'Featured'),
            ExportColumn::make('size_group')->label('Size group'),
            ExportColumn::make('size_label')->label('Size label'),
            ExportColumn::make('short_description')->label('Short description'),
            ExportColumn::make('description')->label('Description')->enabledByDefault(false),
            ExportColumn::make('customer_note')->label('Note for customers'),
            ExportColumn::make('slug')->label('URL slug'),
            ExportColumn::make('meta_title')->label('SEO title')->enabledByDefault(false),
            ExportColumn::make('meta_description')->label('SEO description')->enabledByDefault(false),
            ExportColumn::make('updated_at')->label('Last updated')->enabledByDefault(false),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Product export finished: ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
