<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
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
                                TextEntry::make('customer_name')->label('Name'),
                                TextEntry::make('customer_phone')
                                    ->label('Mobile')
                                    ->formatStateUsing(fn (Order $record) => $record->displayPhone())
                                    ->copyable()
                                    ->copyableState(fn (Order $record) => $record->displayPhone()),
                                TextEntry::make('customer_email')->label('Email')->placeholder('—')->copyable(),
                                TextEntry::make('customer_address')->label('Address')->placeholder('—'),
                                TextEntry::make('customer_comment')->label('Customer comment')->placeholder('—')->columnSpanFull(),
                            ]),
                        Section::make('Items')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->hiddenLabel()
                                    ->table([
                                        RepeatableEntry\TableColumn::make('Product'),
                                        RepeatableEntry\TableColumn::make('SKU'),
                                        RepeatableEntry\TableColumn::make('Qty'),
                                        RepeatableEntry\TableColumn::make('Unit price'),
                                        RepeatableEntry\TableColumn::make('Total'),
                                    ])
                                    ->schema([
                                        TextEntry::make('product_name'),
                                        TextEntry::make('sku')->placeholder('—'),
                                        TextEntry::make('quantity'),
                                        TextEntry::make('unit_price')->formatStateUsing($rs),
                                        TextEntry::make('line_total')->formatStateUsing($rs)->weight('bold'),
                                    ]),
                                TextEntry::make('subtotal')
                                    ->label('Total (cash on delivery)')
                                    ->formatStateUsing($rs)
                                    ->weight('bold')
                                    ->size('lg'),
                            ]),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                TextEntry::make('status')->badge(),
                                TextEntry::make('created_at')->label('Requested')->dateTime('d M Y, h:i A'),
                                TextEntry::make('contacted_at')->label('Contacted')->dateTime('d M Y, h:i A')->placeholder('—'),
                                TextEntry::make('confirmed_at')->label('Confirmed')->dateTime('d M Y, h:i A')->placeholder('—'),
                                TextEntry::make('dispatched_at')->label('Out for delivery')->dateTime('d M Y, h:i A')->placeholder('—'),
                                TextEntry::make('delivered_at')->label('Delivered')->dateTime('d M Y, h:i A')->placeholder('—'),
                                TextEntry::make('cancelled_at')->label('Cancelled')->dateTime('d M Y, h:i A')->placeholder('—'),
                                TextEntry::make('owner_whatsapp_sent_at')->label('Owner WhatsApp alert')->dateTime('d M Y, h:i A')->placeholder('Not sent yet'),
                            ]),
                        Section::make('Admin note')
                            ->schema([
                                TextEntry::make('admin_note')->hiddenLabel()->placeholder('No notes yet')
                                    ->formatStateUsing(fn (?string $state) => nl2br(e($state)))
                                    ->html(),
                            ]),
                        Section::make('Technical')
                            ->collapsed()
                            ->schema([
                                TextEntry::make('ip_address')->label('IP')->placeholder('—'),
                                TextEntry::make('user_agent')->label('Browser')->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
