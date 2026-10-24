<?php

namespace App\Filament\Resources\QuoteRequests;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteRequests\Pages\EditQuoteRequest;
use App\Filament\Resources\QuoteRequests\Pages\ListQuoteRequests;
use App\Filament\Resources\QuoteRequests\Pages\ViewQuoteRequest;
use App\Filament\Resources\QuoteRequests\Schemas\QuoteRequestForm;
use App\Filament\Resources\QuoteRequests\Schemas\QuoteRequestInfolist;
use App\Filament\Resources\QuoteRequests\Tables\QuoteRequestsTable;
use App\Models\QuoteRequest;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

// Bulk / trade quote requests from /bulk-quote
class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Orders';

    protected static ?string $navigationLabel = 'Quote requests';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'name', 'phone', 'business_name'];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = QuoteRequest::where('status', QuoteStatus::New)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return QuoteRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuoteRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuoteRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuoteRequests::route('/'),
            'view' => ViewQuoteRequest::route('/{record}'),
            'edit' => EditQuoteRequest::route('/{record}/edit'),
        ];
    }

    // ---- Shared actions (table + view page) ----

    public static function whatsappAction(): Action
    {
        return Action::make('whatsapp')
            ->label('WhatsApp')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->url(fn (QuoteRequest $record) => $record->whatsappUrl(
                'Assalam o Alaikum ' . $record->name . ', this is ' . SiteSetting::storeName() . ' about your quote request ' . $record->reference . '.'
            ), shouldOpenInNewTab: true);
    }

    public static function callAction(): Action
    {
        return Action::make('call')
            ->label('Call')
            ->icon(Heroicon::OutlinedPhone)
            ->color('gray')
            ->url(fn (QuoteRequest $record) => 'tel:' . $record->phone);
    }

    public static function attachmentAction(): Action
    {
        return Action::make('attachment')
            ->label('Download list')
            ->icon(Heroicon::OutlinedPaperClip)
            ->color('gray')
            ->visible(fn (QuoteRequest $record) => filled($record->attachment_path))
            ->url(fn (QuoteRequest $record) => route('admin.quotes.attachment', $record->id), shouldOpenInNewTab: true);
    }

    public static function changeStatusAction(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->fillForm(fn (QuoteRequest $record) => ['status' => $record->status])
            ->schema([
                Select::make('status')->options(QuoteStatus::class)->required()->native(false),
                Textarea::make('note')->label('Note (optional, internal)')->rows(2)->maxLength(500),
            ])
            ->action(function (QuoteRequest $record, array $data) {
                $status = $data['status'] instanceof QuoteStatus ? $data['status'] : QuoteStatus::from($data['status']);
                $line = now()->format('d M Y H:i') . ' – ' . $status->getLabel() . ' by ' . auth()->user()?->name
                    . (filled($data['note'] ?? null) ? ': ' . $data['note'] : '');

                $record->update([
                    'status'     => $status,
                    'admin_note' => trim(($record->admin_note ? $record->admin_note . "\n" : '') . $line),
                ]);

                Notification::make()->title("{$record->reference} is now {$status->getLabel()}")->success()->send();
            });
    }
}
