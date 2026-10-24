<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderActions;
use App\Models\Order;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('d M Y, h:i A')
                    ->description(fn (Order $record) => $record->created_at->diffForHumans())
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('Mobile')
                    ->formatStateUsing(fn (Order $record) => $record->displayPhone())
                    ->copyable()
                    ->copyableState(fn (Order $record) => $record->displayPhone())
                    ->searchable(query: function (Builder $query, string $search) {
                        // Let staff search "0300 1234567", "3001234567", "+92…"
                        $digits = preg_replace('/\D/', '', $search);
                        $digits = preg_replace('/^(0092|92|0)/', '', $digits);

                        return $digits !== '' ? $query->where('customer_phone', 'like', "%{$digits}%") : $query;
                    }),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('subtotal')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Rs ' . number_format((float) $state))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('customer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class)
                    ->multiple(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                OrderActions::whatsapp()->iconButton(),
                OrderActions::call()->iconButton(),
                ViewAction::make(),
                ActionGroup::make([
                    OrderActions::markContacted(),
                    OrderActions::changeStatus(),
                    EditAction::make(),
                    OrderActions::printSlip(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
