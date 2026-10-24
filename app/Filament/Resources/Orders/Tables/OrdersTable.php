<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderActions;
use App\Filament\Support\Fields;
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
use Illuminate\Support\Carbon;

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
                    ->sortable()
                    // Phones: keep customer, total and status in view (row click still opens the order)
                    ->visibleFrom('md'),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('customer_phone')
                    ->label('Mobile')
                    ->visibleFrom('lg')
                    ->formatStateUsing(fn (Order $record) => $record->displayPhone())
                    ->copyable()
                    ->copyableState(fn (Order $record) => $record->displayPhone())
                    ->searchable(query: Fields::phoneSearch('customer_phone')),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->visibleFrom('lg')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('subtotal')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Rs ' . number_format((float) $state))
                    ->sortable(),
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
                    ->label('Requested between')
                    ->schema([
                        DatePicker::make('from')->label('Requested from')->native(false)->maxDate(now()),
                        DatePicker::make('until')->label('Requested until')->native(false)->maxDate(now()),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date)))
                    // Show the chosen dates above the table, with an ✕ to clear them
                    ->indicateUsing(fn (array $data): array => array_filter([
                        'from'  => ($data['from'] ?? null) ? 'From ' . Carbon::parse($data['from'])->format('d M Y') : null,
                        'until' => ($data['until'] ?? null) ? 'Until ' . Carbon::parse($data['until'])->format('d M Y') : null,
                    ])),
            ])
            ->recordActions([
                OrderActions::whatsapp()->iconButton(),
                OrderActions::call()->iconButton(),
                ViewAction::make()->iconButton()->tooltip('View'),
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
