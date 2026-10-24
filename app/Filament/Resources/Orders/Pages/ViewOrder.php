<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            OrderActions::whatsapp(),
            OrderActions::call(),
            OrderActions::markContacted(),
            OrderActions::changeStatus(),
            ActionGroup::make([
                EditAction::make(),
                OrderActions::printSlip(),
                OrderActions::customerPage(),
            ]),
        ];
    }
}
