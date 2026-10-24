<?php

namespace App\Filament\Resources\HomepageCategorySections\Pages;

use App\Filament\Resources\HomepageCategorySections\HomepageCategorySectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHomepageCategorySections extends ManageRecords
{
    protected static string $resource = HomepageCategorySectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
