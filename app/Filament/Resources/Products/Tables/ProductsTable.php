<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Support\Fields;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('image')
                    ->defaultImageUrl(fn () => \App\Support\Content::placeholderUrl())
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->imageSize(44),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record) => $record->sku ? 'SKU: ' . $record->sku : null)
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Brand')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => Fields::rupees($state))
                    ->description(fn (Product $record) => $record->is_on_sale && $record->sale_price
                        ? 'Sale: ' . Fields::rupees($record->sale_price) : null)
                    ->sortable(),
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state) => $state <= 0 ? 'danger' : ($state < 5 ? 'warning' : null)),
                ToggleColumn::make('is_active')
                    ->label('Visible'),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('company')
                    ->label('Brand')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')->label('Visible'),
                TernaryFilter::make('is_on_sale')->label('On sale'),
                TernaryFilter::make('is_featured')->label('Featured'),
                Filter::make('low_stock')
                    ->label('Low stock (under 5)')
                    ->query(fn (Builder $query) => $query->where('stock', '<', 5)),
            ])
            ->recordActions([
                Action::make('view_on_site')
                    ->label('')
                    ->tooltip('View on website')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Product $record) => route('product.show', $record->slug), shouldOpenInNewTab: true)
                    ->visible(fn (Product $record) => $record->is_active),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('show')
                        ->label('Show on website')
                        ->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('hide')
                        ->label('Hide from website')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
