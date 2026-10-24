<?php

namespace App\Filament\Resources\QuoteRequests\Pages;

use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use Filament\Resources\Pages\ListRecords;

// Quote requests only arrive from the storefront form – no "create" button
class ListQuoteRequests extends ListRecords
{
    protected static string $resource = QuoteRequestResource::class;
}
