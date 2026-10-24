<?php

namespace App\Filament\Imports;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Products → CSV/Excel import (Catalog → Products → Import).
 * Rows are matched to existing products by SKU, then URL slug, then exact name – so re-importing an
 * exported sheet updates products (e.g. new prices) instead of creating duplicates.
 * Category and Brand are given by name; unknown names are created. Images are added in the admin.
 */
class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        $yesNo = fn (string $name, string $label, string $example) => ImportColumn::make($name)
            ->label($label)
            ->boolean()
            ->rules(['nullable', 'boolean'])
            ->example($example)
            ->helperText('yes / no (1 / 0)');

        return [
            ImportColumn::make('name')
                ->label('Name')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['nullable', 'max:255'])
                ->example('7/29 Wire 90m (Fast Cables)'),
            ImportColumn::make('sku')
                ->label('SKU')
                ->rules(['nullable', 'max:100'])
                ->example('FC-729-90'),
            ImportColumn::make('category')
                ->label('Category')
                ->requiredMappingForNewRecordsOnly()
                ->rules(['nullable', 'max:255'])
                ->example('Wires')
                ->helperText('Category name – created if it doesn\'t exist yet')
                ->fillRecordUsing(function (Product $record, ?string $state) {
                    if (filled($state)) {
                        $record->category_id = self::findOrCreate(Category::class, $state)->id;
                    }
                }),
            ImportColumn::make('brand')
                ->label('Brand')
                ->rules(['nullable', 'max:255'])
                ->example('Fast Cables')
                ->helperText('Brand name – created if it doesn\'t exist yet. Leave empty for no brand.')
                ->fillRecordUsing(function (Product $record, ?string $state) {
                    $record->company_id = filled($state) ? self::findOrCreate(Company::class, $state)->id : null;
                }),
            ImportColumn::make('price')
                ->label('Price (Rs)')
                ->requiredMappingForNewRecordsOnly()
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0', 'max:99999999'])
                ->example('9000'),
            ImportColumn::make('sale_price')
                ->label('Sale price (Rs)')
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0', 'max:99999999'])
                ->example('8500')
                ->helperText('Leave empty if not on sale'),
            $yesNo('is_on_sale', 'On sale', 'yes'),
            ImportColumn::make('stock')
                ->label('Stock')
                ->integer()
                ->rules(['nullable', 'integer', 'min:0'])
                ->example('25'),
            $yesNo('is_active', 'Visible', 'yes'),
            $yesNo('is_featured', 'Featured', 'no'),
            ImportColumn::make('size_group')
                ->label('Size group')
                ->rules(['nullable', 'max:100'])
                ->example('7/29 wire'),
            ImportColumn::make('size_label')
                ->label('Size label')
                ->rules(['nullable', 'max:40'])
                ->example('90 m'),
            ImportColumn::make('short_description')
                ->label('Short description')
                ->rules(['nullable', 'max:500'])
                ->example('Pure copper, PVC insulated, 90 metre coil'),
            ImportColumn::make('description')
                ->label('Description')
                ->rules(['nullable', 'max:20000'])
                ->example('Full description (plain text or simple HTML)'),
            ImportColumn::make('customer_note')
                ->label('Note for customers')
                ->rules(['nullable', 'max:300'])
                ->example('Custom lengths available – mention it in the order comment'),
            ImportColumn::make('slug')
                ->label('URL slug')
                ->rules(['nullable', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'])
                ->example('7-29-wire-90m-fast-cables')
                ->helperText('Optional – made from the name when empty'),
            ImportColumn::make('meta_title')
                ->label('SEO title')
                ->rules(['nullable', 'max:70']),
            ImportColumn::make('meta_description')
                ->label('SEO description')
                ->rules(['nullable', 'max:160']),
        ];
    }

    public function resolveRecord(): ?Product
    {
        foreach (['sku', 'slug', 'name'] as $key) {
            $value = trim((string) ($this->data[$key] ?? ''));

            if ($value !== '' && ($existing = Product::where($key, $value)->first())) {
                return $existing;
            }
        }

        return new Product();
    }

    protected function beforeSave(): void
    {
        $product = $this->record;

        // New products need the basics
        if (! $product->exists) {
            foreach (['name' => 'Name', 'category_id' => 'Category', 'price' => 'Price'] as $field => $label) {
                if (blank($product->{$field})) {
                    throw new RowImportFailedException("{$label} is required for a new product.");
                }
            }

            $product->is_active ??= true;
            $product->is_featured ??= false;
            $product->stock ??= 0;
        }

        // A sale price without the "On sale" column means: on sale
        if (! array_key_exists('is_on_sale', $this->data) || $this->data['is_on_sale'] === null) {
            $product->is_on_sale = (float) $product->sale_price > 0;
        }

        if ($product->is_on_sale && (float) $product->sale_price >= (float) $product->price) {
            throw new RowImportFailedException('Sale price must be lower than the price.');
        }

        if (blank($product->slug)) {
            $product->slug = self::uniqueSlug($product->name, $product->id);
        } elseif (Product::where('slug', $product->slug)->whereKeyNot($product->id)->exists()) {
            throw new RowImportFailedException("URL slug \"{$product->slug}\" is already used by another product.");
        }
    }

    private static function findOrCreate(string $model, string $name): Category|Company
    {
        $name = trim($name);

        return $model::where('name', $name)->first()
            ?? $model::where('slug', Str::slug(str_replace(['/', '\\'], ' ', $name)))->first()
            ?? $model::create(['name' => $name, 'slug' => self::uniqueSlugFor($model, $name), 'is_active' => true]);
    }

    private static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug(str_replace(['/', '\\'], ' ', $name)) ?: 'product'; // "7/29 wire" → 7-29-wire
        $slug = $base;

        for ($i = 2; Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private static function uniqueSlugFor(string $model, string $name): string
    {
        $base = Str::slug(str_replace(['/', '\\'], ' ', $name)) ?: 'item';
        $slug = $base;

        for ($i = 2; $model::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Product import finished: ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed – download them to see why, fix and import again.';
        }

        return $body;
    }
}
