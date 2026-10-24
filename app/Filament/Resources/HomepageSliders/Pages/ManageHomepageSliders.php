<?php

namespace App\Filament\Resources\HomepageSliders\Pages;

use App\Filament\Resources\HomepageSliders\HomepageSliderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHomepageSliders extends ManageRecords
{
    protected static string $resource = HomepageSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
