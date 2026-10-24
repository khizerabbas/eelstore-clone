<?php

namespace App\Filament\Resources\QuoteRequests\Schemas;

use App\Models\QuoteRequest;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuoteRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $rs = fn ($state) => 'Rs ' . number_format((float) $state);

        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Customer')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('phone')
                                    ->label('Mobile')
                                    ->formatStateUsing(fn (QuoteRequest $record) => $record->displayPhone())
                                    ->copyable()
                                    ->copyableState(fn (QuoteRequest $record) => $record->displayPhone()),
                                TextEntry::make('business_name')->label('Business')->placeholder('—'),
                                TextEntry::make('city')->label('City / area')->placeholder('—'),
                                TextEntry::make('email')->placeholder('—')->copyable(),
                            ]),
                        Section::make('Items requested')
                            ->schema([
                                TextEntry::make('items')
                                    ->hiddenLabel()
                                    ->placeholder('No list typed – see the attached file')
                                    ->formatStateUsing(fn (?string $state) => nl2br(e($state)))
                                    ->html(),
                                TextEntry::make('attachment_name')
                                    ->label('Attached list')
                                    ->visible(fn (QuoteRequest $record) => filled($record->attachment_path))
                                    ->url(fn (QuoteRequest $record) => route('admin.quotes.attachment', $record->id), shouldOpenInNewTab: true)
                                    ->icon('heroicon-o-paper-clip')
                                    ->color('primary'),
                            ]),
                        Section::make('Items from their cart')
                            ->visible(fn (QuoteRequest $record) => filled($record->cart_items))
                            ->schema([
                                RepeatableEntry::make('cart_items')
                                    ->hiddenLabel()
                                    ->table([
                                        RepeatableEntry\TableColumn::make('Product'),
                                        RepeatableEntry\TableColumn::make('Qty'),
                                        RepeatableEntry\TableColumn::make('Current price'),
                                    ])
                                    ->schema([
                                        TextEntry::make('product_name'),
                                        TextEntry::make('quantity'),
                                        TextEntry::make('line_total')->formatStateUsing($rs),
                                    ]),
                            ]),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                TextEntry::make('status')->badge(),
                                TextEntry::make('reference'),
                                TextEntry::make('created_at')->label('Received')->dateTime('d M Y, h:i A'),
                                TextEntry::make('owner_whatsapp_sent_at')->label('Owner WhatsApp alert')->dateTime('d M Y, h:i A')->placeholder('Not sent yet'),
                            ]),
                        Section::make('Admin note')
                            ->schema([
                                TextEntry::make('admin_note')
                                    ->hiddenLabel()
                                    ->placeholder('No notes yet')
                                    ->formatStateUsing(fn (?string $state) => nl2br(e($state)))
                                    ->html(),
                            ]),
                    ]),
            ]);
    }
}
