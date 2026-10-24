<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Resources\Pages\ManageRecords;

// Messages only arrive from the storefront contact form, so there is no "create" button
class ManageContactMessages extends ManageRecords
{
    protected static string $resource = ContactMessageResource::class;
}
