<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Models\ContactMessage;
use App\Support\PakistaniPhone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

// Read-only inbox for the storefront contact form
class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Orders';

    protected static ?string $navigationLabel = 'Contact messages';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::whereNull('read_at')->count();

        return $count ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('name'),
                TextEntry::make('created_at')->label('Received')->dateTime('d M Y, h:i A'),
                TextEntry::make('email')->copyable(),
                TextEntry::make('phone')->placeholder('—')->copyable(),
                TextEntry::make('subject')->placeholder('—')->columnSpanFull(),
                TextEntry::make('message')
                    ->formatStateUsing(fn (?string $state) => nl2br(e($state)))
                    ->html()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight(fn (ContactMessage $record) => $record->read_at ? null : 'bold'),
                TextColumn::make('subject')
                    ->placeholder('—')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Read')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->after(fn (ContactMessage $record) => $record->read_at ?: $record->forceFill(['read_at' => now()])->save())
                    ->modalFooterActions(fn (ContactMessage $record) => array_filter([
                        Action::make('reply')
                            ->label('Reply by email')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->url('mailto:' . $record->email . '?subject=' . rawurlencode('Re: ' . ($record->subject ?: 'Your message'))),
                        ($phone = PakistaniPhone::normalize($record->phone))
                            ? Action::make('whatsapp')
                                ->label('WhatsApp')
                                ->color('success')
                                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                                ->url('https://wa.me/' . ltrim($phone, '+'), shouldOpenInNewTab: true)
                            : null,
                    ])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageContactMessages::route('/'),
        ];
    }
}
