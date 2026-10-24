<?php

namespace App\Filament\Resources\QuoteRequests\Tables;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use App\Models\QuoteRequest;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class QuoteRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->searchable()->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('d M Y, h:i A')
                    ->description(fn (QuoteRequest $record) => $record->created_at->diffForHumans())
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (QuoteRequest $record) => $record->business_name),
                TextColumn::make('phone')
                    ->label('Mobile')
                    ->formatStateUsing(fn (QuoteRequest $record) => $record->displayPhone())
                    ->searchable(),
                TextColumn::make('items')
                    ->label('Request')
                    ->formatStateUsing(fn (?string $state) => Str::limit(preg_replace('/\s+/', ' ', (string) $state), 60))
                    ->placeholder('(attached list)')
                    ->wrap(),
                IconColumn::make('attachment_path')
                    ->label('File')
                    ->icon(fn (?string $state) => filled($state) ? 'heroicon-o-paper-clip' : null)
                    ->color('gray'),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(QuoteStatus::class)->multiple(),
            ])
            ->recordActions([
                QuoteRequestResource::whatsappAction()->iconButton(),
                QuoteRequestResource::callAction()->iconButton(),
                ViewAction::make(),
                ActionGroup::make([
                    QuoteRequestResource::changeStatusAction(),
                    QuoteRequestResource::attachmentAction(),
                    EditAction::make()->label('Edit note'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
