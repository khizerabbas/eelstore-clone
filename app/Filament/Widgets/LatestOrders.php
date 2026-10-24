<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest order requests')
            ->query(Order::query()->latest()->limit(8))
            ->paginated(false)
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference')->weight('bold'),
                TextColumn::make('created_at')->label('Requested')->since(),
                TextColumn::make('customer_name')->label('Customer'),
                TextColumn::make('customer_phone')
                    ->label('Mobile')
                    ->formatStateUsing(fn (Order $record) => $record->displayPhone()),
                TextColumn::make('subtotal')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Rs ' . number_format((float) $state)),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([
                OrderActions::whatsapp()->iconButton(),
                OrderActions::call()->iconButton(),
            ]);
    }
}
