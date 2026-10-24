<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Check the saved product as customers see it
            Action::make('view_on_site')
                ->label('View on website')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Product $record) => route('product.show', $record->slug), shouldOpenInNewTab: true)
                ->visible(fn (Product $record) => $record->is_active),
            DeleteAction::make(),
        ];
    }
}
