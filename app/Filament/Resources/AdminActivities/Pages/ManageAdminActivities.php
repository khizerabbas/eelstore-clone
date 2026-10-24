<?php

namespace App\Filament\Resources\AdminActivities\Pages;

use App\Filament\Resources\AdminActivities\AdminActivityResource;
use Filament\Resources\Pages\ManageRecords;

// Read-only: entries are written automatically, never created or edited by hand
class ManageAdminActivities extends ManageRecords
{
    protected static string $resource = AdminActivityResource::class;
}
